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
        Schema::table('order_item_components', function (Blueprint $table) {
            $table->decimal('fixed_cost_at_sale', 16, 4)->default(0)->after('avg_cost_at_sale');
            $table->decimal('total_fixed_cost', 16, 4)->default(0)->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item_components', function (Blueprint $table) {
            $table->dropColumn('fixed_cost_at_sale');
            $table->dropColumn('total_fixed_cost');
        });
    }
};
