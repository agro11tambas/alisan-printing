<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lanjutan dari 2026_08_03_000001_add_erp_query_indexes.
 *
 * Tabel products sama sekali belum punya index pada "name", padahal daftar
 * produk selalu ORDER BY name dan hampir semua modul mencari produk lewat
 * kolom itu. Tanpa index MySQL menyortir seluruh tabel setiap request.
 *
 * delivery_orders juga belum punya index untuk kombinasi status + tanggal
 * yang dipakai halaman Delivery Orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('products', ['deleted_at', 'name'], 'products_deleted_name_idx');

        $this->addIndex('delivery_orders', ['status', 'deleted_at', 'delivery_date'], 'delivery_orders_status_deleted_date_idx');
        $this->addIndex('delivery_orders', ['deleted_at', 'delivery_date'], 'delivery_orders_deleted_date_idx');
    }

    public function down(): void
    {
        $this->dropIndex('products', 'products_deleted_name_idx');
        $this->dropIndex('delivery_orders', 'delivery_orders_status_deleted_date_idx');
        $this->dropIndex('delivery_orders', 'delivery_orders_deleted_date_idx');
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumns($table, $columns)
            || Schema::hasIndex($table, $name)
        ) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }
};
