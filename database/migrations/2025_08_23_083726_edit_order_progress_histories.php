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
        Schema::table('order_progress_histories', function (Blueprint $table) {
            $table->string('operator')->nullable()->after('change_quantity');
            $table->string('note')->nullable()->after('operator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_histories', function (Blueprint $table) {
            $table->dropColumn('operator');
            $table->dropColumn('note');
        });
    }
};
