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
            $table->foreignId('purchase_return_item_id')->nullable()->constrained('purchase_return_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_item_id']);
            $table->dropColumn('purchase_return_item_id');
        });
    }
};
