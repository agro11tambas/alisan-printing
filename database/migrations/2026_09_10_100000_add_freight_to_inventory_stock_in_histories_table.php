<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ongkos angkut sekarang diinput waktu barangnya benar-benar diterima
     * (Stock In), bukan lagi waktu Purchase List diketik. Nilainya per satuan
     * beli — sama persis dengan arti purchase_items.freight yang lama — supaya
     * rumus harga modalnya tidak berubah, cuma pindah sumber datanya.
     */
    public function up(): void
    {
        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->decimal('freight', 20, 5)->default(0)->after('stock_in');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->dropColumn('freight');
        });
    }
};
