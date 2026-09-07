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
        Schema::table('inventory_stock_ins_2', function (Blueprint $table) {
            $table->longText('receipt_image')->nullable()->after('waybill_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock_ins_2', function (Blueprint $table) {
            $table->dropColumn('receipt_image');
        });
    }
};
