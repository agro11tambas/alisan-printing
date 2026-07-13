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
        if (!Schema::hasColumn('discounts', 'apply_on_ecommerce')) {
            Schema::table('discounts', function (Blueprint $table) {
                $table->enum('apply_on_ecommerce', ['None', 'Category'])->default('None')->after('apply_on');
            });
        }

        Schema::create('discount_ecommerce_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->foreignId('ecommerce_product_category_id')->constrained('ecommerce_product_categories', 'id', 'fk_disc_ec_cat')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_ecommerce_categories');
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('apply_on_ecommerce');
        });
    }
};
