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
        Schema::create('defect_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('set null');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('inventory_id')->nullable()->constrained('inventories_2')->onDelete('set null');
            $table->foreignId('inventory_stock_out_id')->nullable()->constrained('inventory_stock_outs_2')->onDelete('set null');
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items_2')->onDelete('set null');
            $table->date('defect_date')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('defect_type')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->string('defect_image')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defect_products');
    }
};
