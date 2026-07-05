<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_variant_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variant_group_id')
                ->constrained('ecommerce_variant_groups')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('product_id')->nullable();

            $table->string('alias');
            $table->decimal('extra_price', 15, 2)->default(0);

            $table->string('image')->nullable();
            $table->string('video')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_variant_options');
    }
};
