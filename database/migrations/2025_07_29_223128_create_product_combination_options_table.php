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
        Schema::create('product_combination_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_combination_id')->constrained('product_combinations')->onDelete('cascade');
            $table->foreignId('product_variant_option_id')->constrained('product_variant_options')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_combination_options');
    }
};
