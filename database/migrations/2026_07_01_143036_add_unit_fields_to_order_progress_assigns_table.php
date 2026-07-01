<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->unsignedBigInteger('product_unit_conversion_id')->nullable()->after('product_id');
            $table->string('unit_name')->nullable()->after('product_unit_conversion_id');
            $table->decimal('unit_conversion_value', 15, 4)->default(1)->after('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropColumn([
                'product_unit_conversion_id',
                'unit_name',
                'unit_conversion_value',
            ]);
        });
    }
};
