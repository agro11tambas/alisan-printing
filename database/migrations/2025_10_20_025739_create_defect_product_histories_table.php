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
        Schema::create('defect_product_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defect_product_id')->constrained('defect_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->integer('quantity')->default(0);
            $table->string('action_type');
            $table->text('note')->nullable();
            $table->date('action_date')->nullable();
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
        Schema::dropIfExists('defect_product_histories');
    }
};
