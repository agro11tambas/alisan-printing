<?php

namespace App\Console\Commands;

use App\Models\PurchaseItem;
use Illuminate\Console\Command;

/**
 * Bandingkan kolom cermin purchase_items.stock_in dengan sumber aslinya,
 * inventory_items_2.stock_in.
 *
 * HANYA MELAPORKAN. Perintah ini tidak pernah menulis: selisihnya bisa lahir
 * dari inventory item yatim atau ganda, jadi menyamakan kolom secara membabi
 * buta justru menimpa angka yang benar dengan angka yang salah.
 *
 * Kalau ada baris yang muncul di sini, telusuri dulu dengan
 * `inventory:trace-stock-in` sebelum memutuskan sisi mana yang keliru.
 */
class SyncPurchaseItemStockIn extends Command
{
    protected $signature = 'purchase:check-item-stock-in
        {--purchase= : Batasi ke satu purchase (id atau purchase_number)}';

    protected $description = 'Laporkan purchase item yang kolom stock_in-nya beda dari total inventory item (read-only)';

    public function handle(): int
    {
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

        $this->warn($mismatched->count().' purchase item tidak sinkron. Perintah ini tidak mengubah apa pun.');
        $this->line('Telusuri dulu: php artisan inventory:trace-stock-in <purchase_number>');

        return self::SUCCESS;
    }
}
