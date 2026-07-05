<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('ecommerce_product_categories')
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('product_units')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('brand')->nullable();
            $table->string('main_image')->nullable();
            $table->longText('description')->nullable();

            $table->integer('multiple_qty')->default(1);
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};
