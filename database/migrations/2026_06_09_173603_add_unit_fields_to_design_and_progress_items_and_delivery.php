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
        Schema::table('design_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_conversion_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_conversion_id');
            $table->decimal('unit_conversion_value', 15, 2)->default(1)->after('unit_name');
        });

        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_conversion_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_conversion_id');
            $table->decimal('unit_conversion_value', 15, 2)->default(1)->after('unit_name');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_conversion_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_conversion_id');
            $table->decimal('unit_conversion_value', 15, 2)->default(1)->after('unit_name');
        });

        Schema::table('delivery_list_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_conversion_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_conversion_id');
            $table->decimal('unit_conversion_value', 15, 2)->default(1)->after('unit_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('design_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
            ]);
        });

        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
            ]);
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
            ]);
        });

        Schema::table('delivery_list_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
            ]);
        });
    }
};
