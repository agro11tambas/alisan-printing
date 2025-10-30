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
            $table->integer('incoming_stock')->default(0)->after('available_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_stocks', function (Blueprint $table) {
            $table->dropColumn('incoming_stock');
        });
    }
};
