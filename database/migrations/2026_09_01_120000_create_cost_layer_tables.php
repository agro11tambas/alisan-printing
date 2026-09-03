<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul FIFO cost layer.
 *
 * cost_layers      : batch stok masuk beserta harga modalnya (snapshot purchase).
 * cost_consumptions: jejak batch mana saja yang dimakan tiap baris penjualan.
 * order_item_costs : ringkasan modal & margin per baris penjualan, dibaca export.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // purchase_item | opening_stock
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference', 100)->nullable();

            // Tanggal yang menentukan antrian FIFO. Sengaja memakai tanggal
            // purchase, bukan created_at, supaya purchase yang diinput mundur
            // tetap mengantre pada posisi yang benar.
            $table->dateTime('layer_date');

            $table->decimal('qty_in', 20, 4)->default(0);
            $table->decimal('qty_remaining', 20, 4)->default(0);

            // Modal per satuan dasar (pcs), bukan per satuan beli.
            $table->decimal('unit_cost', 20, 5)->default(0);

            $table->timestamps();

            $table->index(['product_id', 'layer_date', 'id'], 'cost_layers_fifo_index');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('cost_consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('cost_layer_id')->nullable();

            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 5)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);

            // 1 = qty ini tidak tertutup batch purchase mana pun, harganya taksiran.
            $table->boolean('is_estimated')->default(false);

            $table->timestamps();

            $table->index('order_item_id');
            $table->index('cost_layer_id');
            $table->index(['product_id', 'order_id']);
        });

        Schema::create('order_item_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->unique();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_bundle_id')->nullable();

            $table->decimal('qty_base', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 5)->default(0);
            $table->decimal('revenue', 20, 4)->default(0);
            $table->decimal('margin', 20, 4)->default(0);
            $table->boolean('is_estimated')->default(false);

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_costs');
        Schema::dropIfExists('cost_consumptions');
        Schema::dropIfExists('cost_layers');
    }
};
