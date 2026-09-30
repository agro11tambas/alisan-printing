<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memberi Stock Opname harga modal, supaya selisih hasil hitung fisik ikut
 * menilai persediaan — bukan cuma menggeser angka kuantitas.
 *
 * stock_opnames               : harga modal dan nilai selisihnya.
 * stock_opname_cost_layers    : batch FIFO mana saja yang dipakai satu baris
 *                               opname. Tanpa ini nilai persediaan setelah
 *                               opname tidak bisa direkonsiliasi ke belakang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            // Sudah lama ditulis oleh controller tapi kolomnya belum pernah
            // ada, jadi nilainya hilang diam-diam. Jejak stok sebelum opname
            // justru bagian yang paling dicari saat audit selisih.
            $table->decimal('old_stock', 20, 4)->default(0)->after('quantity');
            $table->decimal('diff', 20, 4)->default(0)->after('old_stock');

            // Harga modal per satuan dasar.
            //
            // Gain : diisi saat disimpan (lihat cost_source) lalu DIBEKUKAN —
            //        dia jadi harga batch baru, sama seperti harga pembelian.
            // Loss : hasil hitungan FIFO, ditulis ulang tiap rebuild. Tidak
            //        pernah diinput manual: harganya milik batch yang termakan.
            $table->decimal('unit_cost', 20, 5)->default(0)->after('diff');
            $table->decimal('cost_value', 20, 4)->default(0)->after('unit_cost');

            // avg_cost | last_cost | manual | fifo
            $table->string('cost_source', 20)->nullable()->after('cost_value');

            // 1 = sebagian kuantitas Loss tidak tertutup batch mana pun,
            // harganya taksiran. Baris inilah yang perlu dibereskan datanya.
            $table->boolean('is_estimated')->default(false)->after('cost_source');
        });

        Schema::create('stock_opname_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('product_id');

            // null = kuantitas ini tidak tertutup batch mana pun (Loss yang
            // melebihi sisa antrian), harganya taksiran.
            $table->unsignedBigInteger('cost_layer_id')->nullable();

            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 5)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->boolean('is_estimated')->default(false);

            $table->timestamps();

            $table->index('stock_opname_id');
            $table->index('cost_layer_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_cost_layers');

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropColumn([
                'old_stock',
                'diff',
                'unit_cost',
                'cost_value',
                'cost_source',
                'is_estimated',
            ]);
        });
    }
};
