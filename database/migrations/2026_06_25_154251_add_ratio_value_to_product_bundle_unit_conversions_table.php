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
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->decimal('ratio_value', 15, 4)->nullable()->after('conversion_value');
        });

        Schema::table('product_bundle_unit_conversions', function (Blueprint $table) {
            $table->decimal('ratio_value', 15, 4)->nullable()->after('conversion_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->dropColumn('ratio_value');
        });

        Schema::table('product_bundle_unit_conversions', function (Blueprint $table) {
            $table->dropColumn('ratio_value');
        });
    }
};
