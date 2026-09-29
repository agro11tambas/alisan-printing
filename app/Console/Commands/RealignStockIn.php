<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\FifoCostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Bagi ulang Stock In satu Purchase Order ke Purchase List anaknya.
 *
 * Form Stock In membagi tiap penerimaan ke PL urut tanggal beli dan berhenti di
 * sisa masing-masing PL. Edit History dulu tidak punya batas itu, jadi satu PL
 * bisa terisi melewati qty-nya. Kelebihan di PL paling depan menggeser seluruh
 * rantai di belakangnya, dan PL terakhir terlihat kurang padahal total di level
 * PO sudah pas.
 *
 * Perintah ini menghitung ulang pembagian itu dari data yang ada:
 *
 *   - Total tiap surat jalan DIPERTAHANKAN. Yang diubah hanya ke PL mana isinya
 *     dicatat, bukan berapa barang yang masuk.
 *   - Total per produk juga tidak berubah, jadi inventory_stocks tidak disentuh
 *     sama sekali. Tidak ada stok yang bertambah atau berkurang.
 *   - inventory_items_2.stock_in dan purchase_items.stock_in dihitung ulang dari
 *     baris histori, bukan ditebak.
 *
 * Default-nya hanya melaporkan. Sebelum menulis, nilai lama disimpan ke file
 * JSON dan perintah restore-nya dicetak.
 */
class RealignStockIn extends Command
{
    protected $signature = 'inventory:realign-stock-in
        {purchase? : Nomor Purchase Order (atau id-nya)}
        {--product= : Batasi ke satu produk (potongan nama)}
        {--apply : Tulis perubahan ke database (tanpa ini hanya laporan)}
        {--restore= : Kembalikan dari file backup hasil --apply sebelumnya}';

    protected $description = 'Bagi ulang Stock In PO ke Purchase List anaknya sesuai urutan FIFO (aman: total tidak berubah)';

    public function handle(): int
    {
        if ($file = $this->option('restore')) {
            return $this->restore($file);
        }

        if (! $this->argument('purchase')) {
            $this->error('Nomor Purchase Order wajib diisi.');

            return self::FAILURE;
        }

        $purchase = Purchase::where('purchase_number', $this->argument('purchase'))
            ->orWhere('id', $this->argument('purchase'))
            ->first();

        if (! $purchase) {
            $this->error('Purchase tidak ditemukan: '.$this->argument('purchase'));

            return self::FAILURE;
        }

        $purchaseOrderId = $purchase->parent_purchase_id ?? $purchase->id;

        $plan = $this->buildPlan($purchaseOrderId);

        if ($plan === null) {
            return self::FAILURE;
        }

        if ($plan['changes'] === []) {
            $this->info('Pembagian Stock In sudah benar. Tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->report($plan);

        if (! $this->option('apply')) {
            $this->warn('MODE LAPORAN. Belum ada yang ditulis.');
            $this->line('Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        return $this->apply($plan);
    }

    /**
     * Susun pembagian yang benar tanpa menyentuh database.
     *
     * @return array{changes: array, items: array, products: array, groups: array}|null
     */
    private function buildPlan(int $purchaseOrderId): ?array
    {
        // Inventory item seluruh PL anak PO ini, urut tanggal beli — urutan yang
        // sama dipakai form Stock In waktu membagi penerimaan.
        $items = DB::table('inventory_items_2 as ii')
            ->join('inventories_2 as inv', 'inv.id', '=', 'ii.inventory_id')
            ->join('purchases as pl', 'pl.id', '=', 'inv.purchase_id')
            ->leftJoin('products as p', 'p.id', '=', 'ii.product_id')
            ->where('pl.parent_purchase_id', $purchaseOrderId)
            ->whereNull('ii.deleted_at')
            ->whereNull('inv.deleted_at')
            ->whereNull('pl.deleted_at')
            ->when($this->option('product'), fn ($q, $name) => $q->where('p.name', 'like', '%'.$name.'%'))
            ->orderBy('pl.purchase_date')
            ->orderBy('ii.id')
            ->select([
                'ii.id', 'ii.product_id', 'ii.purchase_item_id', 'ii.qty_base', 'ii.quantity',
                'ii.stock_in', 'p.name as product_name', 'pl.purchase_number',
            ])
            ->get();

        if ($items->isEmpty()) {
            $this->error('Tidak ada inventory item aktif untuk PO ini.');

            return null;
        }

        $histories = DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as sh', 'sh.id', '=', 'h.inventory_stock_in_id')
            ->whereIn('h.inventory_item_id', $items->pluck('id'))
            ->orderBy('sh.change_date')
            ->orderBy('h.id')
            ->select([
                'h.id', 'h.inventory_item_id', 'h.stock_in',
                'sh.waybill_number', 'sh.change_date',
            ])
            ->get();

        if ($histories->isEmpty()) {
            $this->error('Tidak ada histori Stock In untuk PO ini.');

            return null;
        }

        $itemsById = $items->keyBy('id');
        $changes = [];
        $groupSummaries = [];

        foreach ($items->pluck('product_id')->unique() as $productId) {
            $productItems = $items->where('product_id', $productId)->values();
            $productItemIds = $productItems->pluck('id')->all();

            $productHistories = $histories->whereIn('inventory_item_id', $productItemIds);

            // Satu penerimaan bisa melahirkan beberapa header (satu per inventory),
            // jadi yang menyatukannya adalah nomor surat jalan + tanggalnya.
            $groups = $productHistories->groupBy(
                fn ($history) => $history->waybill_number.'|'.$history->change_date
            );

            $filled = array_fill_keys($productItemIds, 0);

            foreach ($groups as $key => $rows) {
                $delivered = (int) $rows->sum('stock_in');
                $remaining = $delivered;
                $target = [];

                foreach ($productItems as $item) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $capacity = (int) ($item->qty_base ?: $item->quantity);
                    $room = $capacity - $filled[$item->id];

                    if ($room <= 0) {
                        continue;
                    }

                    $take = min($remaining, $room);
                    $target[$item->id] = $take;
                    $filled[$item->id] += $take;
                    $remaining -= $take;
                }

                [$waybill, $date] = explode('|', $key);

                $groupSummaries[] = [
                    'product' => $productItems->first()->product_name ?? $productId,
                    'waybill' => $waybill,
                    'date' => $date,
                    'delivered' => $delivered,
                    'unplaced' => $remaining,
                ];

                if ($remaining > 0) {
                    $this->error(
                        'Surat jalan '.$waybill.' ('.($productItems->first()->product_name ?? '-').') kelebihan '
                        .number_format($remaining).' pcs: tidak ada sisa PL yang bisa menampung.'
                    );
                    $this->line('Ini berarti qty PO/PL-nya yang perlu dibetulkan dulu, bukan pembagiannya.');

                    return null;
                }

                // Pasangkan angka baru itu ke baris histori yang sudah ada.
                $rowsByItem = $rows->groupBy('inventory_item_id');

                foreach ($target as $itemId => $value) {
                    if (! $rowsByItem->has($itemId)) {
                        $this->error(
                            'Surat jalan '.$waybill.' butuh baris histori baru untuk inventory item '.$itemId
                            .'. Perintah ini sengaja tidak membuat baris baru.'
                        );

                        return null;
                    }
                }

                foreach ($rowsByItem as $itemId => $itemRows) {
                    // Kalau satu item punya beberapa baris di surat jalan yang sama,
                    // seluruh angka ditaruh di baris pertama dan sisanya dinolkan.
                    $value = $target[$itemId] ?? 0;

                    foreach ($itemRows->values() as $index => $row) {
                        $newValue = $index === 0 ? $value : 0;

                        if ((int) $row->stock_in !== $newValue) {
                            $changes[] = [
                                'history_id' => $row->id,
                                'inventory_item_id' => $row->inventory_item_id,
                                'product' => $itemsById[$row->inventory_item_id]->product_name ?? '-',
                                'purchase_number' => $itemsById[$row->inventory_item_id]->purchase_number ?? '-',
                                'waybill' => $waybill,
                                'old' => (int) $row->stock_in,
                                'new' => $newValue,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'changes' => $changes,
            'items' => $items,
            'products' => $items->pluck('product_id')->unique()->values()->all(),
            'groups' => $groupSummaries,
        ];
    }

    private function report(array $plan): void
    {
        $this->line('Perubahan baris histori (total tiap surat jalan tetap):');
        $this->table(
            ['Histori', 'Purchase List', 'Produk', 'Surat jalan', 'Lama', 'Baru', 'Selisih'],
            collect($plan['changes'])->map(fn ($change) => [
                $change['history_id'],
                $change['purchase_number'],
                mb_strimwidth($change['product'], 0, 22, '...'),
                $change['waybill'],
                number_format($change['old']),
                number_format($change['new']),
                number_format($change['new'] - $change['old']),
            ])->all()
        );

        // Ringkasan per inventory item: ini yang dilihat operator di layar.
        $delta = [];
        foreach ($plan['changes'] as $change) {
            $delta[$change['inventory_item_id']] = ($delta[$change['inventory_item_id']] ?? 0)
                + $change['new'] - $change['old'];
        }

        $rows = [];
        foreach ($plan['items'] as $item) {
            if (! isset($delta[$item->id]) || $delta[$item->id] === 0) {
                continue;
            }

            $capacity = (int) ($item->qty_base ?: $item->quantity);
            $after = (int) $item->stock_in + $delta[$item->id];

            $rows[] = [
                $item->purchase_number,
                mb_strimwidth($item->product_name ?? '-', 0, 22, '...'),
                number_format($capacity),
                number_format((int) $item->stock_in),
                number_format($after),
                $after === $capacity ? 'pas' : number_format($after - $capacity),
            ];
        }

        if ($rows !== []) {
            $this->newLine();
            $this->line('Akibatnya di tiap Purchase List:');
            $this->table(['Purchase List', 'Produk', 'Qty (pcs)', 'Stock In lama', 'Stock In baru', 'Sisa'], $rows);
        }

        $this->newLine();
        $this->info('Total per produk tidak berubah, jadi stok gudang tidak bergeser sama sekali.');
    }

    private function apply(array $plan): int
    {
        $backup = [
            'created_at' => now()->toDateTimeString(),
            'histories' => [],
            'inventory_items' => [],
            'purchase_items' => [],
        ];

        foreach ($plan['changes'] as $change) {
            $backup['histories'][$change['history_id']] = $change['old'];
        }

        $touchedItemIds = collect($plan['changes'])->pluck('inventory_item_id')->unique()->values();

        $touchedPurchaseItemIds = $plan['items']
            ->whereIn('id', $touchedItemIds)
            ->pluck('purchase_item_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($plan['items']->whereIn('id', $touchedItemIds) as $item) {
            $backup['inventory_items'][$item->id] = (int) $item->stock_in;
        }

        foreach (DB::table('purchase_items')->whereIn('id', $touchedPurchaseItemIds)->get() as $purchaseItem) {
            $backup['purchase_items'][$purchaseItem->id] = (int) $purchaseItem->stock_in;
        }

        $path = 'stock-in-realign-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT));

        DB::transaction(function () use ($plan, $touchedItemIds, $touchedPurchaseItemIds) {
            foreach ($plan['changes'] as $change) {
                DB::table('inventory_stock_in_histories_2')
                    ->where('id', $change['history_id'])
                    ->update(['stock_in' => $change['new'], 'updated_at' => now()]);
            }

            // Dihitung ulang dari histori, bukan digeser pakai selisih, supaya
            // hasilnya tidak bergantung pada angka lama yang mungkin sudah salah.
            foreach ($touchedItemIds as $itemId) {
                $total = (int) DB::table('inventory_stock_in_histories_2')
                    ->where('inventory_item_id', $itemId)
                    ->sum('stock_in');

                DB::table('inventory_items_2')
                    ->where('id', $itemId)
                    ->update(['stock_in' => $total, 'updated_at' => now()]);
            }

            foreach ($touchedPurchaseItemIds as $purchaseItemId) {
                $total = (int) DB::table('inventory_items_2')
                    ->where('purchase_item_id', $purchaseItemId)
                    ->whereNull('deleted_at')
                    ->sum('stock_in');

                DB::table('purchase_items')
                    ->where('id', $purchaseItemId)
                    ->update(['stock_in' => $total, 'updated_at' => now()]);
            }
        });

        // Batch FIFO lahir dari baris histori, jadi harus disusun ulang.
        app(FifoCostService::class)->rebuild($plan['products']);

        Purchase::syncApprovalProgressFromPurchaseItems($touchedPurchaseItemIds->all());

        $this->newLine();
        $this->info('Selesai. '.count($plan['changes']).' baris histori dibetulkan.');
        $this->line('Backup nilai lama: '.Storage::disk('local')->path($path));
        $this->line('Kalau perlu dikembalikan:');
        $this->line('  php artisan inventory:realign-stock-in --restore='.$path);

        return self::SUCCESS;
    }

    private function restore(string $file): int
    {
        if (! Storage::disk('local')->exists($file)) {
            $this->error('File backup tidak ditemukan: '.Storage::disk('local')->path($file));

            return self::FAILURE;
        }

        $backup = json_decode(Storage::disk('local')->get($file), true);

        if (! is_array($backup) || ! isset($backup['histories'])) {
            $this->error('Isi file backup tidak dikenali.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($backup) {
            foreach ($backup['histories'] as $id => $value) {
                DB::table('inventory_stock_in_histories_2')
                    ->where('id', $id)
                    ->update(['stock_in' => $value, 'updated_at' => now()]);
            }

            foreach ($backup['inventory_items'] ?? [] as $id => $value) {
                DB::table('inventory_items_2')
                    ->where('id', $id)
                    ->update(['stock_in' => $value, 'updated_at' => now()]);
            }

            foreach ($backup['purchase_items'] ?? [] as $id => $value) {
                DB::table('purchase_items')
                    ->where('id', $id)
                    ->update(['stock_in' => $value, 'updated_at' => now()]);
            }
        });

        $this->info('Nilai lama dikembalikan dari backup '.$backup['created_at'].'.');
        $this->warn('Jalankan rebuild HPP kalau perlu: php artisan fifo:rebuild');

        return self::SUCCESS;
    }
}
