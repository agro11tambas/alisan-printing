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
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->integer('stock_out_request')->after('stock_out')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropColumn('stock_out_request');
        });
    }
};
