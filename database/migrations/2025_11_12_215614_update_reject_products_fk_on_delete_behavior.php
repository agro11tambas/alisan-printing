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
        Schema::table('reject_products', function (Blueprint $table) {
            // hapus constraint lama
            $table->dropForeign(['order_progress_batch_id']);

            // tambahkan constraint baru
            $table->foreign('order_progress_batch_id')
                ->references('id')->on('order_progress_batches')
                ->nullOnDelete(); // atau ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reject_products', function (Blueprint $table) {
            $table->dropForeign(['order_progress_batch_id']);
            $table->foreign('order_progress_batch_id')
                ->references('id')->on('order_progress_batches');
        });
    }
};
