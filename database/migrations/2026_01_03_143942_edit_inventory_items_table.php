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
            $table->foreignId('production_warehouse_id')->after('inventory_warehouse_id')->nullable()->constrained('production_warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropForeign(['production_warehouse_id']);
            $table->dropColumn('production_warehouse_id');
        });
    }
};
