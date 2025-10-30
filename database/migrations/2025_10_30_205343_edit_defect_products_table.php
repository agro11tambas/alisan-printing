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
        Schema::table('defect_products', function (Blueprint $table) {
            $table->foreignId('sale_return_id')->after('inventory_item_id')->nullable()->constrained('sale_returns')->onDelete('cascade');
            $table->foreignId('sale_return_item_id')->after('sale_return_id')->nullable()->constrained('sale_return_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defect_products', function (Blueprint $table) {
            $table->dropForeign(['sale_return_id']);
            $table->dropColumn('sale_return_id');
            $table->dropForeign(['sale_return_item_id']);
            $table->dropColumn('sale_return_item_id');
        });
    }
};
