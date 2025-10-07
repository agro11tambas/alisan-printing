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
            $table->foreignId('material_request_id')->nullable()->after('sale_return_id')->constrained('material_requests')->onDelete('cascade');
            $table->foreignId('material_receipt_id')->nullable()->after('material_request_id')->constrained('material_receipts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->dropForeign(['material_request_id']);
            $table->dropForeign(['material_receipt_id']);
            $table->dropColumn(['material_request_id', 'material_receipt_id']);
        });
    }
};
