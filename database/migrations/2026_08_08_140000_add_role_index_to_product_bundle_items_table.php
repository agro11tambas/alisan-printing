<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pencarian bundle selalu lewat pasangan (product_id, role): satu subquery
     * untuk primary, satu untuk secondary. Kolom role belum terindeks, jadi
     * MySQL menyaringnya baris per baris setelah memakai indeks product_id.
     */
    public function up(): void
    {
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->index(['product_id', 'role'], 'pbi_product_role_index');
            $table->index(['bundle_id', 'role'], 'pbi_bundle_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->dropIndex('pbi_product_role_index');
            $table->dropIndex('pbi_bundle_role_index');
        });
    }
};
