<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            // available_quantity dibuat tanpa default, jadi semua insert yang
            // tidak menyebut kolom ini (mis. firstOrCreate saat edit sale list)
            // gagal di MySQL strict mode.
            $table->integer('available_quantity')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->integer('available_quantity')->change();
        });
    }
};
