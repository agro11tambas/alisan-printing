<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ========== ORDER PROGRESS ITEMS ==========
        Schema::table('order_progress_items', function (Blueprint $table) {
            // Drop foreign key constraint lama
            $table->dropForeign(['product_id']);

            // Ubah kolom jadi nullable
            $table->foreignId('product_id')->nullable()->change();

            // Tambah foreign key baru dengan nullOnDelete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== ORDER PROGRESS ASSIGNS ==========
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== ORDER PROGRESS HISTORIES ==========
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== DESIGN ITEMS ==========
        Schema::table('design_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== DELIVERY ORDER ITEMS ==========
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== DELIVERY LIST ITEMS ==========
        Schema::table('delivery_list_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        // ========== ORDER ITEMS ==========
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        // Rollback: kembalikan ke cascade
        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('design_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('delivery_list_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }
};
