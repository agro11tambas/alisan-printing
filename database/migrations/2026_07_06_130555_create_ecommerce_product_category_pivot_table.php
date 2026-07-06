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
        Schema::create('ecommerce_product_category_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_product_id');
            $table->foreign('ecommerce_product_id', 'fk_ep_id')->references('id')->on('ecommerce_products')->onDelete('cascade');
            
            $table->foreignId('ecommerce_product_category_id');
            $table->foreign('ecommerce_product_category_id', 'fk_epc_id')->references('id')->on('ecommerce_product_categories')->onDelete('cascade');
            $table->timestamps();
        });

        // Migrate existing data
        $products = DB::table('ecommerce_products')->whereNotNull('category_id')->get();
        foreach ($products as $product) {
            DB::table('ecommerce_product_category_pivot')->insert([
                'ecommerce_product_id' => $product->id,
                'ecommerce_product_category_id' => $product->category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop category_id column
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropForeign('ecommerce_products_category_id_foreign');
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('ecommerce_product_categories')->onDelete('set null');
        });

        // Restore data
        $pivots = DB::table('ecommerce_product_category_pivot')->get();
        foreach ($pivots as $pivot) {
            DB::table('ecommerce_products')
                ->where('id', $pivot->ecommerce_product_id)
                ->update(['category_id' => $pivot->ecommerce_product_category_id]);
        }

        Schema::dropIfExists('ecommerce_product_category_pivot');
    }
};
