<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_unit_conversion_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_unit_conversions')
                ->nullOnDelete();

            $table->string('unit_name')
                ->nullable()
                ->after('product_unit_conversion_id');

            $table->decimal('unit_conversion_value', 15, 2)
                ->default(1)
                ->after('unit_name');

            $table->decimal('qty_base', 15, 2)
                ->default(0)
                ->after('unit_conversion_value');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['product_unit_conversion_id']);

            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
                'qty_base',
            ]);
        });
    }
};
