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
        Schema::table('inventory_stock_out_histories', function (Blueprint $table) {
            $table->foreignId('inventory_stock_out_id')->constrained('inventory_stock_outs')->after('id')->onDelete('cascade');
            $table->foreignId('purchase_product_id')->constrained('purchase_products')->after('inventory_stock_out_id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock_out_histories', function (Blueprint $table) {
            $table->dropForeign(['inventory_stock_out_id']);
            $table->dropForeign(['purchase_product_id']);
        });
    }
};
