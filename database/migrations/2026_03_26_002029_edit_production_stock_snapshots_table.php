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
        Schema::table('production_stock_snapshots', function (Blueprint $table) {
            $table->integer('closing_stock')->default(0)->after('opening_stock');
            $table->integer('stock_in_today')->default(0)->after('closing_stock');
            $table->integer('assign_today')->default(0)->after('stock_in_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_stock_snapshots', function (Blueprint $table) {
            $table->dropColumn(['closing_stock', 'stock_in_today', 'assign_today']);
        });
    }
};
