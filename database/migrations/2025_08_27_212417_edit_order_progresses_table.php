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
        Schema::table('order_progresses', function (Blueprint $table) {
            $table->foreignId('order_item_id')->after('order_id')->constrained('order_items')->onDelete('cascade')->nullable();
            $table->foreignId('product_id')->after('order_item_id')->constrained('products')->onDelete('cascade')->nullable();
            $table->integer('quantity')->after('product_id')->default(0);
            $table->integer('completed_quantity')->after('quantity')->default(0);
            $table->string('status')->after('completed_quantity')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progresses', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropColumn('order_item_id');
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->dropColumn('quantity');
            $table->dropColumn('completed_quantity');
            $table->dropColumn('status');
        });
    }
};
