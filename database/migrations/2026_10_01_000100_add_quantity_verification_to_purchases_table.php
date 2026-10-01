<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifikasi quantity Purchase List anak: penanda bahwa jumlah barang pada PL
 * sudah dicek dan benar, lengkap dengan siapa dan kapan.
 *
 * Sengaja TIDAK memakai kolom `verified` yang sudah ada — kolom itu berarti
 * "pembayaran terverifikasi" dan di-reset tiap kali PL diedit, jadi artinya
 * berbeda dan siklus hidupnya pun berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->timestamp('quantity_verified_at')->nullable()->after('verified');
            $table->unsignedBigInteger('quantity_verified_by')->nullable()->after('quantity_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['quantity_verified_at', 'quantity_verified_by']);
        });
    }
};
