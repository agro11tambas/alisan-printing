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
        Schema::table('purchases', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_edit_histories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_return_edit_histories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventories_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_stock_ins_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_stock_outs_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_stock_out_histories_2', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('material_requests', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('material_request_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('material_request_item_histories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('delivery_item_histories', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_edit_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_return_edit_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventories_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_stock_ins_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_stock_outs_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_stock_out_histories_2', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('material_request_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('material_request_item_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('delivery_item_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
