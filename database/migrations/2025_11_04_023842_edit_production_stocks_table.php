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
            $table->integer('pending_waiting_list')->default(0)->after('available_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_stocks', function (Blueprint $table) {
            $table->dropColumn('pending_waiting_list');
        });
    }
};
