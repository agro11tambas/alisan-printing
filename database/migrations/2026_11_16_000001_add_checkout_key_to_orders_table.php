<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'checkout_key')) {
                // Kode unik per checkout dari website. Request yang sama dikirim
                // ulang (klik dobel, coba lagi setelah timeout) mengembalikan
                // order yang sudah ada, bukan membuat order kedua. Order dari
                // ERP dan order lama tidak punya isi di sini.
                $table->string('checkout_key', 64)->nullable()->unique()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'checkout_key')) {
                $table->dropUnique(['checkout_key']);
                $table->dropColumn('checkout_key');
            }
        });
    }
};
