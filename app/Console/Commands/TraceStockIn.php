<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bongkar asal-usul angka Stock In satu Purchase Order / Purchase List.
 *
 * Dipakai waktu "sisa" di modul Stock In tidak cocok dengan yang tampil di
 * Purchase List. Perintah ini hanya membaca — tidak ada satu pun tulisan ke
 * database — supaya aman dijalankan langsung di produksi.
 *
 * Yang dibandingkan per purchase item:
 *   qty_base        target penerimaan (pcs)
 *   PI stock_in     kolom cermin di purchase_items
 *   inv stock_in    total inventory_items_2 (sumber kebenaran)
 *   histori         total baris inventory_stock_in_histories_2
 *
 * Tiga angka terakhir seharusnya sama. Yang berbeda menunjukkan sisi mana yang
 * rusak: kolom cermin basi, inventory item ganda atau yatim, atau histori dan
 * inventory item sudah tidak sejalan.
 */
class TraceStockIn extends Command
{
    protected $signature = 'inventory:trace-stock-in
        {purchase : Nomor purchase (PO atau PL) atau id-nya}
        {--product= : Saring ke satu produk (potongan nama)}
        {--history : Tampilkan baris histori stock in beserta siapa dan kapan mencatatnya}';

    protected $description = 'Telusuri angka Stock In sebuah PO/PL sampai ke baris inventory item (read-only)';

    public function handle(): int
    {
        $key = $this->argument('purchase');

        $purchase = Purchase::withTrashed()
            ->where('purchase_number', $key)
            ->orWhere('id', $key)
            ->first();

        if (! $purchase) {
            $this->error('Purchase tidak ditemukan: '.$key);

            return self::FAILURE;
        }

        // PO diperiksa bersama seluruh PL anaknya, karena Stock In dicatat di PL.
        $purchases = Purchase::withTrashed()
            ->where('id', $purchase->id)
            ->orWhere('parent_purchase_id', $purchase->id)
            ->orderBy('id')
            ->get();

        $this->info('Purchase: '.$purchase->purchase_number.' (id '.$purchase->id.', status '.$purchase->status.')');
        $this->line('Cakupan: '.$purchases->count().' purchase (induk + anak).');
        $this->newLine();

        foreach ($purchases as $current) {
            $this->traceOne($current);
        }

        $this->newLine();
        $this->line('Flag DEL = baris sudah soft delete, INV-DEL = inventory induknya yang terhapus.');
        $this->line('Kolom "inv stock_in" adalah angka yang dipakai modul Stock In dan listing Purchase List.');

        return self::SUCCESS;
    }

    private function traceOne(Purchase $purchase): void
    {
        $items = DB::table('purchase_items as pi')
            ->leftJoin('products as p', 'p.id', '=', 'pi.product_id')
            ->where('pi.purchase_id', $purchase->id)
            ->when($this->option('product'), fn ($q, $name) => $q->where('p.name', 'like', '%'.$name.'%'))
            ->orderBy('pi.id')
            ->select([
                'pi.id', 'pi.quantity', 'pi.unit_name', 'pi.unit_conversion_value',
                'pi.qty_base', 'pi.stock_in', 'pi.deleted_at', 'p.name as product_name',
            ])
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $label = $purchase->purchase_number.($purchase->deleted_at ? '  [DELETED]' : '');
        $this->line('== '.$label.' ('.$purchase->status.')');

        $rows = [];

        foreach ($items as $item) {
            $inventoryItems = DB::table('inventory_items_2 as ii')
                ->leftJoin('inventories_2 as inv', 'inv.id', '=', 'ii.inventory_id')
                ->where('ii.purchase_item_id', $item->id)
                ->orderBy('ii.id')
                ->select([
                    'ii.id', 'ii.inventory_id', 'ii.qty_base', 'ii.stock_in',
                    'ii.deleted_at', 'inv.deleted_at as inventory_deleted_at',
                    'inv.purchase_number',
                ])
                ->get();

            $historyByItem = DB::table('inventory_stock_in_histories_2')
                ->whereIn('inventory_item_id', $inventoryItems->pluck('id'))
                ->groupBy('inventory_item_id')
                ->select('inventory_item_id', DB::raw('SUM(stock_in) as total'))
                ->pluck('total', 'inventory_item_id');

            $conversion = rtrim(rtrim(number_format((float) $item->unit_conversion_value, 2, '.', ''), '0'), '.');

            $rows[] = [
                'PI '.$item->id,
                mb_strimwidth($item->product_name ?? '-', 0, 26, '...'),
                number_format((float) $item->quantity).' '.$item->unit_name.' x'.$conversion,
                number_format((float) $item->qty_base),
                number_format((float) $item->stock_in),
                number_format((float) $inventoryItems->sum('stock_in')),
                number_format((float) $historyByItem->sum()),
                $item->deleted_at ? 'DEL' : '',
            ];

            foreach ($inventoryItems as $inventoryItem) {
                $flags = [];
                if ($inventoryItem->deleted_at) {
                    $flags[] = 'DEL';
                }
                if ($inventoryItem->inventory_deleted_at) {
                    $flags[] = 'INV-DEL';
                }

                $rows[] = [
                    '   inv item '.$inventoryItem->id,
                    'inventory '.$inventoryItem->inventory_id.' ('.($inventoryItem->purchase_number ?? '-').')',
                    '',
                    number_format((float) $inventoryItem->qty_base),
                    '',
                    number_format((float) $inventoryItem->stock_in),
                    number_format((float) ($historyByItem[$inventoryItem->id] ?? 0)),
                    implode(' ', $flags),
                ];
            }
        }

        $this->table(
            ['Baris', 'Produk / asal', 'Qty', 'qty_base', 'PI stock_in', 'inv stock_in', 'histori', 'Flag'],
            $rows
        );

        if ($this->option('history')) {
            $this->dumpHistories($items->pluck('id'));
        }

        $this->dumpOrphans($purchase);
    }

    /**
     * Baris histori mentah: yang membedakan "sekali catat" dari "dicatat lalu
     * diedit". Status header Add/Edit Stock In dan jam pencatatannya yang
     * memberi tahu dari mana angka yang tidak wajar itu masuk.
     */
    private function dumpHistories($purchaseItemIds): void
    {
        $histories = DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_items_2 as ii', 'ii.id', '=', 'h.inventory_item_id')
            ->leftJoin('inventory_stock_ins_2 as sh', 'sh.id', '=', 'h.inventory_stock_in_id')
            ->leftJoin('users as u', 'u.id', '=', 'sh.user_id')
            ->whereIn('ii.purchase_item_id', $purchaseItemIds)
            ->orderBy('h.id')
            ->select([
                'h.id', 'h.inventory_item_id', 'h.stock_in', 'h.created_at',
                'sh.status', 'sh.waybill_number', 'u.name as user_name',
            ])
            ->get();

        if ($histories->isEmpty()) {
            return;
        }

        $this->table(
            ['Histori', 'Inv item', 'Stock In', 'Status header', 'Surat jalan', 'Oleh', 'Dicatat'],
            $histories->map(fn ($history) => [
                $history->id,
                $history->inventory_item_id,
                number_format((float) $history->stock_in),
                $history->status ?? '-',
                $history->waybill_number ?? '-',
                mb_strimwidth($history->user_name ?? '-', 0, 18, '...'),
                $history->created_at,
            ])->all()
        );
    }

    private function dumpOrphans(Purchase $purchase): void
    {
        // Inventory item milik purchase ini yang tidak menunjuk purchase item mana
        // pun. Baris seperti ini ikut terhitung di modul Stock In tapi tidak pernah
        // muncul di Purchase List, jadi sering jadi biang selisih.
        $orphans = DB::table('inventory_items_2 as ii')
            ->join('inventories_2 as inv', 'inv.id', '=', 'ii.inventory_id')
            ->leftJoin('products as p', 'p.id', '=', 'ii.product_id')
            ->where('inv.purchase_id', $purchase->id)
            ->whereNull('ii.purchase_item_id')
            ->select(['ii.id', 'ii.qty_base', 'ii.stock_in', 'ii.deleted_at', 'p.name as product_name'])
            ->get();

        if ($orphans->isNotEmpty()) {
            $this->warn('Inventory item tanpa purchase_item_id di '.$purchase->purchase_number.':');
            $this->table(
                ['Inv item', 'Produk', 'qty_base', 'stock_in', 'Flag'],
                $orphans->map(fn ($orphan) => [
                    $orphan->id,
                    mb_strimwidth($orphan->product_name ?? '-', 0, 26, '...'),
                    number_format((float) $orphan->qty_base),
                    number_format((float) $orphan->stock_in),
                    $orphan->deleted_at ? 'DEL' : '',
                ])->all()
            );
        }
    }
}
