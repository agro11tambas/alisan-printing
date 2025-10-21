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
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->foreignId('order_progress_item_id')->nullable()->after('order_item_id')->constrained('order_progress_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropForeign(['order_progress_item_id']);
            $table->dropColumn('order_progress_item_id');
        });
    }
};
