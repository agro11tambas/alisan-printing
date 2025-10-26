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
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->integer('completed_quantity')->default(0)->after('change_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->dropColumn('completed_quantity');
        });
    }
};
