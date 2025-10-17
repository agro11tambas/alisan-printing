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
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->decimal('avg_cost_at_return', 15, 2)->default(0)->after('price');
            $table->decimal('total_cost', 15, 2)->default(0)->after('avg_cost_at_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('avg_cost_at_return');
            $table->dropColumn('total_cost');
        });
    }
};
