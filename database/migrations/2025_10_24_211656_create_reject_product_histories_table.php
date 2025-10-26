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
        Schema::create('reject_product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reject_product_id')->constrained('reject_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('inventory_warehouse_id')->nullable()->constrained('inventory_warehouses');
            $table->integer('quantity')->default(0);
            $table->date('date');
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reject_product_histories');
    }
};
