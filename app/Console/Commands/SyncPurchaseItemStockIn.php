<?php

namespace App\Console\Commands;

use App\Models\PurchaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Samakan kolom cermin purchase_items.stock_in dengan sumber aslinya,
 * inventory_items_2.stock_in.
 *
 * Kolom itu hanya diisi waktu Stock In dibuat. Edit history stock in dulu cuma
 * menggeser inventory item, jadi halaman detail Purchase List bisa menampilkan
 * sisa (mis. 38/50) padahal modul Stock In sudah menghitung sisa nol.
 *
 * Default-nya hanya melaporkan. Tambahkan --apply untuk benar-benar menulis.
 */
class SyncPurchaseItemStockIn extends Command
{
    protected $signature = 'purchase:sync-item-stock-in
        {--apply : Tulis perubahan ke database (tanpa ini hanya laporan)}
        {--purchase= : Batasi ke satu purchase (id atau purchase_number)}';

    protected $description = 'Samakan purchase_items.stock_in dengan total stock_in inventory item';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('MODE LAPORAN. Tidak ada yang ditulis. Tambahkan --apply untuk memperbaiki.');
        }

        $query = PurchaseItem::query()
            ->with(['purchase:id,purchase_number', 'purchaseProduct:id,name'])
            ->withSum('inventoryItems as inventory_stock_in', 'stock_in');

        if ($purchase = $this->option('purchase')) {
            $query->whereHas(
                'purchase',
                fn ($q) => $q->where('id', $purchase)->orWhere('purchase_number', $purchase)
            );
        }

        $mismatched = $query->get()->filter(
            fn (PurchaseItem $item) => (int) $item->stock_in !== (int) ($item->inventory_stock_in ?? 0)
        );

        if ($mismatched->isEmpty()) {
            $this->info('Semua purchase item sudah sinkron.');

            return self::SUCCESS;
        }

        $this->table(
            ['Item', 'Purchase', 'Produk', 'Qty Base', 'PI Stock In', 'Inventory Stock In', 'Selisih'],
            $mismatched->map(function (PurchaseItem $item) {
                $inventory = (int) ($item->inventory_stock_in ?? 0);

                return [
                    $item->id,
                    $item->purchase->purchase_number ?? $item->purchase_id,
                    mb_strimwidth($item->purchaseProduct->name ?? $item->product_name ?? '-', 0, 30, '...'),
                    number_format((float) ($item->qty_base ?: $item->quantity)),
                    number_format((int) $item->stock_in),
                    number_format($inventory),
                    number_format($inventory - (int) $item->stock_in),
                ];
            })->all()
        );

        $this->info($mismatched->count().' purchase item tidak sinkron.');

        if (! $apply) {
            $this->warn('Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($mismatched) {
            foreach ($mismatched as $item) {
                // updateQuietly supaya perbaikan data tidak memicu event model
                // yang bisa menulis riwayat edit palsu.
                $item->updateQuietly(['stock_in' => (int) ($item->inventory_stock_in ?? 0)]);
            }
        });

        $this->info('Selesai. '.$mismatched->count().' purchase item disamakan.');

        return self::SUCCESS;
    }
}
