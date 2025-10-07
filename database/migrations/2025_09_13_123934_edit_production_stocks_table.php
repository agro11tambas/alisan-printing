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
        Schema::table('production_stocks', function (Blueprint $table) {
            $table->integer('opening_available_quantity')->default(0)->after('opening_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_stocks', function (Blueprint $table) {
            $table->dropColumn('opening_available_quantity');
        });
    }
};
