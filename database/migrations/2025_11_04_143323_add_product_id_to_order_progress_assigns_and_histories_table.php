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
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_progress_assigns', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('order_progress_item_id');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            }
        });

        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            if (!Schema::hasColumn('order_progress_histories_2', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('order_progress_item_id');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            if (Schema::hasColumn('order_progress_assigns', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
        });

        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            if (Schema::hasColumn('order_progress_histories_2', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
        });
    }
};
