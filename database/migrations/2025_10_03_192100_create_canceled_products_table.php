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
        Schema::create('canceled_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_stock_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            // relasi ke sumber
            $table->unsignedBigInteger('sale_return_id')->nullable();
            $table->unsignedBigInteger('sale_return_item_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_item_id')->nullable();

            $table->integer('quantity');   // qty asal
            $table->date('date');
            $table->string('type'); // 'damaged', 'expired', 'other'
            $table->string('status')->default('pending'); // 'canceled', 'returned'

            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // index & foreign key
            $table->foreign('production_stock_id')->references('id')->on('production_stocks')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('production_warehouses')->nullOnDelete();

            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->nullOnDelete();
            $table->foreign('sale_return_item_id')->references('id')->on('sale_return_items')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canceled_products');
    }
};
