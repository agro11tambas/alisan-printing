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
            $table->decimal('total_amount_product', 15, 2)->default(0)->after('remaining_amount');
            $table->decimal('paid_amount_product', 15, 2)->default(0)->after('total_amount_product');
            $table->decimal('remaining_amount_product', 15, 2)->default(0)->after('paid_amount_product');

            $table->decimal('total_amount_freight', 15, 2)->default(0)->after('remaining_amount_product');
            $table->decimal('paid_amount_freight', 15, 2)->default(0)->after('total_amount_freight');
            $table->decimal('remaining_amount_freight', 15, 2)->default(0)->after('paid_amount_freight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('total_amount_product');
            $table->dropColumn('paid_amount_product');
            $table->dropColumn('remaining_amount_product');
            $table->dropColumn('total_amount_freight');
            $table->dropColumn('paid_amount_freight');
            $table->dropColumn('remaining_amount_freight');
        });
    }
};
