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
        Schema::table('product_variant_options', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('value');
            $table->integer('opening_stock')->default(0)->after('price');
            $table->decimal('opening_rate', 10, 2)->default(0)->after('opening_stock');
            $table->integer('purchase_stock')->default(0)->after('opening_rate');
            $table->integer('inventory_stock')->default(0)->after('purchase_stock');
            $table->integer('stock_after_sales')->default(0)->after('inventory_stock');
            $table->decimal('avg_cost', 10, 2)->default(0)->after('stock_after_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_options', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('opening_stock');
            $table->dropColumn('opening_rate');
            $table->dropColumn('purchase_stock');
            $table->dropColumn('inventory_stock');
            $table->dropColumn('stock_after_sales');
            $table->dropColumn('avg_cost');
        });
    }
};
