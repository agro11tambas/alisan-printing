<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rumus tunggal "Waiting List" di laporan items (produksi, gabungan, dan
 * inventory). Sebelumnya rumusnya ditulis ulang di tiap controller sebagai
 * total design - total assigned, dan angkanya tidak turun untuk order yang
 * sudah selesai tanpa lewat assign.
 *
 * Waiting list = design yang masih hidup
 *              - yang sudah di-assign
 *              - yang selesai langsung tanpa assign
 *
 * Semua angka dalam satuan dasar (base unit).
 */
class PendingWaitingList
{
    /**
     * Ekspresi SQL waiting list untuk satu produk, dipakai di dalam selectRaw.
     *
     * @param string $productIdColumn kolom product_id dari query luar,
     *                                misal 'production_stocks.product_id'
     */
    public static function sqlExpression(string $productIdColumn): string
    {
        $design = self::designSql($productIdColumn);
        $assigned = self::assignedSql($productIdColumn);
        $direct = self::directCompletedSql($productIdColumn);

        return "GREATEST(({$design}) - ({$assigned}) - ({$direct}), 0)";
    }

    /**
     * Waiting list per produk untuk sekumpulan product_id.
     *
     * @param  iterable<int> $productIds
     * @return Collection<int, float> product_id => waiting list
     */
    public static function byProduct(iterable $productIds): Collection
    {
        $ids = collect($productIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('products')
            ->whereIn('products.id', $ids)
            ->selectRaw('products.id AS product_id, ' . self::sqlExpression('products.id') . ' AS pending')
            ->pluck('pending', 'product_id')
            ->map(fn ($value) => (float) $value);
    }

    /**
     * Total qty design (base unit).
     *
     * Design induk wajib masih hidup: penghapusan order lewat Sale List
     * meng-soft-delete designs lebih dulu, sehingga cascade di model Order
     * tidak lagi menemukan design itu dan design_items-nya tertinggal hidup.
     */
    private static function designSql(string $productIdColumn): string
    {
        return "COALESCE((
            SELECT SUM(di.quantity * COALESCE(di.unit_conversion_value, 1))
            FROM design_items di
            INNER JOIN designs d ON d.id = di.design_id
            WHERE di.product_id = {$productIdColumn}
            AND di.deleted_at IS NULL
            AND d.deleted_at IS NULL
        ), 0)";
    }

    /**
     * Total yang sudah di-assign ke operator (assigned_quantity sudah base unit).
     */
    private static function assignedSql(string $productIdColumn): string
    {
        return "COALESCE((
            SELECT SUM(a.assigned_quantity)
            FROM order_progress_assigns a
            WHERE a.product_id = {$productIdColumn}
            AND a.deleted_at IS NULL
        ), 0)";
    }

    /**
     * Progress item yang selesai tanpa pernah di-assign.
     *
     * Item alur polosan dibuat langsung dengan completed_quantity = quantity
     * dan tidak pernah punya baris order_progress_assigns, jadi kalau hanya
     * mengurangi assigned, item ini terus terhitung sebagai waiting list
     * padahal sudah selesai.
     *
     * Di jalur ini quantity dan completed_quantity masih satuan input dan
     * baris progress-nya dibuat tanpa unit_conversion_value (kolomnya
     * default 1, bukan NULL), jadi konversi diambil dari design item-nya --
     * angka yang sama dengan yang dipakai di total design. Item yang punya
     * assign dikecualikan: completed_quantity-nya sudah tercakup di assigned.
     */
    private static function directCompletedSql(string $productIdColumn): string
    {
        return "COALESCE((
            SELECT SUM(
                LEAST(opi.completed_quantity, opi.quantity)
                * COALESCE(di.unit_conversion_value, opi.unit_conversion_value, 1)
            )
            FROM order_progress_items opi
            INNER JOIN order_progresses_2 op ON op.id = opi.order_progress_id
            LEFT JOIN design_items di ON di.id = opi.design_item_id
            WHERE opi.product_id = {$productIdColumn}
            AND opi.deleted_at IS NULL
            AND op.deleted_at IS NULL
            AND opi.completed_quantity > 0
            AND NOT EXISTS (
                SELECT 1 FROM order_progress_assigns a
                WHERE a.order_progress_item_id = opi.id
                AND a.deleted_at IS NULL
            )
        ), 0)";
    }
}
