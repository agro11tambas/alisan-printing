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
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->foreignId('production_stock_id')->nullable()->constrained('production_stocks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->dropForeign(['production_stock_id']);
            $table->dropColumn('production_stock_id');
        });
    }
};
