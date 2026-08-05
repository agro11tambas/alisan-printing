<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discount_price_modes')) {
            Schema::create('discount_price_modes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
                $table->foreignId('price_mode_id')->constrained('price_modes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['discount_id', 'price_mode_id'], 'discount_price_mode_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_price_modes');
    }
};
