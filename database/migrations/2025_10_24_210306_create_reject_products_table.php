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
        Schema::create('reject_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('order_progress_id')->nullable()->constrained('order_progresses_2');
            $table->foreignId('order_progress_batch_id')->nullable()->constrained('order_progress_batches');
            $table->date('reject_date');
            $table->integer('quantity')->default(0);
            $table->integer('eliminated_quantity')->default(0);
            $table->integer('returned_quantity')->default(0);
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
        Schema::dropIfExists('reject_products');
    }
};
