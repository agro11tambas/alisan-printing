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
            if (Schema::hasColumn('reject_products', 'order_progress_history_id')) {

                try {
                    $table->dropForeign(['order_progress_history_id']);
                } catch (\Throwable $e) {
                    // FK sudah tidak ada, abaikan
                }

                $table->unsignedBigInteger('order_progress_history_id')
                    ->nullable()
                    ->change();

                $table->foreign('order_progress_history_id')
                    ->references('id')
                    ->on('order_progress_histories_2')
                    ->onDelete('set null');
            }
        });

        Schema::table('defect_products', function (Blueprint $table) {
            if (Schema::hasColumn('defect_products', 'order_progress_history_id')) {

                try {
                    $table->dropForeign(['order_progress_history_id']);
                } catch (\Throwable $e) {
                    // FK sudah tidak ada
                }

                $table->unsignedBigInteger('order_progress_history_id')
                    ->nullable()
                    ->change();

                $table->foreign('order_progress_history_id')
                    ->references('id')
                    ->on('order_progress_histories_2')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reject_products', function (Blueprint $table) {
            $table->dropForeign(['order_progress_history_id']);
            $table->unsignedBigInteger('order_progress_history_id')->nullable(false)->change();
            $table->foreign('order_progress_history_id')
                ->references('id')
                ->on('order_progress_histories_2')
                ->onDelete('cascade');
        });

        Schema::table('defect_products', function (Blueprint $table) {
            $table->dropForeign(['order_progress_history_id']);
            $table->unsignedBigInteger('order_progress_history_id')->nullable(false)->change();
            $table->foreign('order_progress_history_id')
                ->references('id')
                ->on('order_progress_histories_2')
                ->onDelete('cascade');
        });
    }
};
