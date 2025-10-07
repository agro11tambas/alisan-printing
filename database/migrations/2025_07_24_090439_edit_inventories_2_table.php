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
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->foreignId('purchase_return_id')->nullable()->after('purchase_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('sale_return_id')->nullable()->after('order_id')->constrained('sale_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropForeign(['sale_return_id']);
            $table->dropColumn(['purchase_return_id', 'sale_return_id']);
        });
    }
};
