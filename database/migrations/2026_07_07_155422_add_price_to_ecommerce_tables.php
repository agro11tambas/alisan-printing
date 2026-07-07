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
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('description');
        });

        Schema::table('ecommerce_variant_options', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('alias');
        });

        Schema::table('ecommerce_variant_combinations', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('lid_option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('ecommerce_variant_options', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('ecommerce_variant_combinations', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
