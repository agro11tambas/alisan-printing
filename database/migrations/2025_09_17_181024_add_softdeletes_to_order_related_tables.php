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
        Schema::table('order_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_progresses_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_progress_batches', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('order_edit_histories', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_progresses_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_progress_batches', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('order_edit_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
