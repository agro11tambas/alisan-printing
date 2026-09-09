<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Soft delete inventory_items_2 yang induk inventories_2-nya sudah dihapus.
 *
 * Penghapusan Purchase memanggil Purchase::deleting -> inventories()->delete(),
 * yang dulu tidak menyentuh item sama sekali. Item yang tertinggal tetap
 * terhitung oleh laporan yang hanya memeriksa deleted_at milik item, sehingga
 * Incoming Stock membengkak dan tidak pernah bisa turun.
 *
 * Cascade di Inventory::booted() menghentikan produksi baris yatim baru.
 * Command ini membereskan yang sudah terlanjur ada.
 *
 * Default-nya hanya melaporkan. Tambahkan --apply untuk benar-benar menulis.
 */
class RepairOrphanInventoryItems extends Command
{
    protected $signature = 'inventory:repair-orphan-items {--apply : Tulis perubahan ke database (tanpa ini hanya laporan)}';

    protected $description = 'Soft delete inventory_items_2 yang induk inventories_2-nya sudah terhapus';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('MODE LAPORAN. Tidak ada yang ditulis. Tambahkan --apply untuk memperbaiki.');
        }

        $orphans = InventoryItem::query()
            ->select([
                'inventory_items_2.id',
                'inventory_items_2.inventory_id',
                'inventory_items_2.product_id',
                'inventory_items_2.qty_base',
                'inventory_items_2.quantity',
                'inventory_items_2.unit_conversion_value',
                'inventory_items_2.stock_in',
                'inventories_2.deleted_at as inventory_deleted_at',
                'products.name as product_name',
            ])
            ->join('inventories_2', 'inventories_2.id', '=', 'inventory_items_2.inventory_id')
            ->leftJoin('products', 'products.id', '=', 'inventory_items_2.product_id')
            ->whereNotNull('inventories_2.deleted_at')
            ->orderBy('inventory_items_2.id')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Tidak ada inventory item yatim.');

            return self::SUCCESS;
        }

        $rows = [];
        $incomingHilang = 0.0;

        foreach ($orphans as $item) {
            $base = (float) ($item->qty_base ?: ($item->quantity * ($item->unit_conversion_value ?: 1)));
            $sisa = max(0, $base - (float) $item->stock_in);
            $incomingHilang += $sisa;

            $rows[] = [
                $item->id,
                $item->inventory_id,
                mb_strimwidth($item->product_name ?? '-', 0, 30, '...'),
                number_format($base),
                number_format((float) $item->stock_in),
                number_format($sisa),
            ];
        }

        $this->table(
            ['Item', 'Inventory', 'Produk', 'Qty Base', 'Stock In', 'Sisa (incoming palsu)'],
            $rows
        );

        $this->info(sprintf(
            'Ditemukan %d item yatim di %d inventory. Incoming palsu yang hilang setelah dibersihkan: %s.',
            $orphans->count(),
            $orphans->pluck('inventory_id')->unique()->count(),
            number_format($incomingHilang)
        ));

        if (! $apply) {
            $this->warn('Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        // Disamakan dengan deleted_at induknya supaya restore inventory nanti
        // mengembalikan item yang sama persis.
        DB::transaction(function () use ($orphans) {
            foreach ($orphans->groupBy('inventory_deleted_at') as $deletedAt => $group) {
                InventoryItem::whereIn('id', $group->pluck('id'))
                    ->update(['deleted_at' => $deletedAt]);
            }
        });

        $this->info('Selesai. '.$orphans->count().' item ikut ditandai terhapus.');

        return self::SUCCESS;
    }
}
