<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel suppliers sama sekali belum punya index selain primary key.
 *
 * Padahal namanya dipakai untuk mengurutkan (form Purchase Order/List memuat
 * seluruh supplier ORDER BY name) dan untuk mencari — filter supplier di
 * listing Purchase Order menjalankan whereHas dengan LIKE 'kata%', yang bisa
 * memakai index karena wildcard-nya hanya di belakang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers') || ! Schema::hasColumn('suppliers', 'name')) {
            return;
        }

        if (Schema::hasIndex('suppliers', 'suppliers_name_lookup_index')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->index('name', 'suppliers_name_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers') || ! Schema::hasIndex('suppliers', 'suppliers_name_lookup_index')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropIndex('suppliers_name_lookup_index');
        });
    }
};
