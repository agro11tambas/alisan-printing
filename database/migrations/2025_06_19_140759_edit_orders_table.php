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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('payment_status');
            $table->decimal('grand_total', 15, 2)->default(0)->after('discount');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('grand_total');
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount', 'grand_total', 'paid_amount', 'remaining_amount']);
        });
    }
};
