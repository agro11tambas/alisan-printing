<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('order_progress_id')->nullable()->change();
            $table->unsignedBigInteger('order_progress_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropForeign(['order_progress_id']);
            $table->dropForeign(['order_progress_item_id']);
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('order_progress_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_progress_item_id')->nullable(false)->change();
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->foreign('order_progress_id')
                ->references('id')
                ->on('order_progresses_2')
                ->cascadeOnDelete();

            $table->foreign('order_progress_item_id')
                ->references('id')
                ->on('order_progress_items')
                ->cascadeOnDelete();
        });
    }
};
