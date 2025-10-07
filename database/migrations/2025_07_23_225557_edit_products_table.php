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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('purchase_stock')->nullable()->after('short_description');
            $table->integer('inventory_stock')->nullable()->after('purchase_stock');
            $table->integer('stock_after_sales')->nullable()->after('inventory_stock');
            $table->decimal('avg_cost')->nullable()->after('stock_after_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('purchase_stock');
            $table->dropColumn('inventory_stock');
            $table->dropColumn('stock_after_sales');
            $table->dropColumn('avg_cost');
        });
    }
};
