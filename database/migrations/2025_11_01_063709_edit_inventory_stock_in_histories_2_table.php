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
        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('stock_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
