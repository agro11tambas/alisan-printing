<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen fisik yang dibawa barang waktu Purchase List dibuat: invoice, surat
 * jalan supplier, dan surat jalan ekspedisi. Masing-masing punya nomor dan foto.
 *
 * Kolom `waybill_image` yang sudah ada dipakai apa adanya sebagai foto surat
 * jalan supplier, supaya data lama tidak perlu dipindah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('invoice_image')->nullable()->after('waybill_image');
            $table->string('supplier_waybill_number')->nullable()->after('invoice_image');
            $table->string('expedition_waybill_number')->nullable()->after('supplier_waybill_number');
            $table->string('expedition_waybill_image')->nullable()->after('expedition_waybill_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_image',
                'supplier_waybill_number',
                'expedition_waybill_number',
                'expedition_waybill_image',
            ]);
        });
    }
};
