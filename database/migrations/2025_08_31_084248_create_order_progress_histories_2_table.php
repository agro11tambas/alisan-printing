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
        Schema::create('order_progress_histories_2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_progress_batch_id')->constrained('order_progress_batches')->onDelete('cascade');
            $table->foreignId('order_progress_item_id')->constrained('order_progress_items')->onDelete('cascade');
            $table->integer('change_quantity')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_progress_histories_2');
    }
};
