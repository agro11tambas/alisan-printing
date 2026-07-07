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
        Schema::create('ecommerce_variant_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_product_id')->constrained('ecommerce_products')->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained('ecommerce_variant_options')->cascadeOnDelete();
            $table->foreignId('lid_option_id')->constrained('ecommerce_variant_options')->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_variant_combinations');
    }
};
