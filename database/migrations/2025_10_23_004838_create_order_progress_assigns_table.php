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
        Schema::create('order_progress_assigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_progress_item_id')->constrained('order_progress_items')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->integer('assigned_quantity')->default(0);
            $table->integer('completed_quantity')->default(0);
            $table->integer('defect_quantity')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_progress_assigns');
    }
};
