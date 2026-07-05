<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_variant_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ecommerce_product_id')
                ->constrained('ecommerce_products')
                ->cascadeOnDelete();

            $table->string('name'); // PRODUCT OPTION, LID OPTION
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_variant_groups');
    }
};
