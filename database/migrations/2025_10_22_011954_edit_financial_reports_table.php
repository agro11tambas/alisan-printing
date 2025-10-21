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
        Schema::table('financial_reports', function (Blueprint $table) {
            $table->decimal('gross_profit_at_fixed_cost', 15, 2)->default(0)->after('gross_profit');
            $table->decimal('net_profit_at_fixed_cost', 15, 2)->default(0)->after('net_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_reports', function (Blueprint $table) {
            $table->dropColumn('gross_profit_at_fixed_cost');
            $table->dropColumn('net_profit_at_fixed_cost');
        });
    }
};
