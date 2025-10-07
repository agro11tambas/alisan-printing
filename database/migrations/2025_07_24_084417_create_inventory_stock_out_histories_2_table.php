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
        Schema::create('inventory_stock_out_histories_2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_stock_out_id')->constrained('inventory_stock_outs_2')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items_2')->onDelete('cascade');
            $table->integer('stock_out');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_out_histories_2');
    }
};
