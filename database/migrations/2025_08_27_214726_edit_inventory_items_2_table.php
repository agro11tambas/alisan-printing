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
            $table->foreignId('material_request_item_id')->nullable()->after('purchase_return_item_id')->constrained('material_request_items')->onDelete('cascade');
            $table->foreignId('material_receipt_item_id')->nullable()->after('material_request_item_id')->constrained('material_receipt_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropForeign(['material_request_item_id']);
            $table->dropForeign(['material_receipt_item_id']);
            $table->dropColumn(['material_request_item_id', 'material_receipt_item_id']);
        });
    }
};
