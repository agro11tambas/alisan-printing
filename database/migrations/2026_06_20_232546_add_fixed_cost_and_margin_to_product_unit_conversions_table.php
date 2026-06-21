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
            $table->decimal('fixed_cost', 15, 2)->default(0)->after('purchase_price');
            $table->decimal('margin', 15, 2)->default(0)->after('fixed_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->dropColumn(['fixed_cost', 'margin']);
        });
    }
};
