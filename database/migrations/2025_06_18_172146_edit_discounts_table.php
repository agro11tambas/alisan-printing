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
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('minimum_based_on')->after('amount');
            $table->decimal('minimum_qty_or_amount', 10, 2)->after('minimum_based_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('minimum_based_on');
            $table->dropColumn('minimum_qty_or_amount');
        });
    }
};
