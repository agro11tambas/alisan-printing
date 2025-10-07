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
        Schema::create('material_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_receipt_id')->constrained('material_receipts')->cascadeOnDelete();
            $table->foreignId('material_request_item_id')->constrained('material_request_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('received_qty')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_receipt_items');
    }
};
