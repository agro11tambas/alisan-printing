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
            $table->unsignedBigInteger('order_progress_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_progress_item_id')->nullable(false)->change();
        });
    }
};
