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
        Schema::create('inventory_items_2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories_2')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('stock_in')->nullable();
            $table->integer('remaining_stock_in')->nullable();
            $table->integer('stock_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items_2');
    }
};
