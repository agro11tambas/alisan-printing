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
        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('unit_id')
                ->constrained('product_units')
                ->cascadeOnDelete();
            $table->decimal('conversion_value', 15, 2)->default(1);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_unit_conversions');
    }
};
