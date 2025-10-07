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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_product_id')->constrained('purchase_products')->onDelete('cascade');
            $table->integer('stok_awal')->default(0);
            $table->integer('barang_masuk')->default(0);
            $table->integer('barang_keluar')->default(0);
            $table->integer('stok_akhir')->default(0); // Bisa juga dihitung on the fly jika tidak disimpan
            $table->string('periode')->nullable(); // Misal: '2025-06' atau 'minggu ke-2'
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
