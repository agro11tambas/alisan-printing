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
            $table->foreignId('product_bundle_unit_conversion_id')
                ->nullable()
                ->after('product_unit_conversion_id')
                ->constrained('product_bundle_unit_conversions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_bundle_unit_conversion_id']);
            $table->dropColumn('product_bundle_unit_conversion_id');
        });
    }
};
