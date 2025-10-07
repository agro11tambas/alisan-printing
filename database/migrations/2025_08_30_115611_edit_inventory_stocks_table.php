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
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->integer('opening_stock')->after('product_id')->default(0);
            $table->decimal('opening_rate', 10, 2)->after('opening_stock')->default(0);
            $table->integer('inventory_stock')->after('opening_rate')->default(0);
            $table->integer('incoming_stock')->after('inventory_stock')->default(0);
            $table->integer('stock_after_sales')->after('inventory_stock')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            //
        });
    }
};
