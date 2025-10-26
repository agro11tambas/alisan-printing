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
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->integer('change_quantity')->default(0)->after('assigned_quantity');
            $table->integer('reject_quantity')->default(0)->after('defect_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropColumn('change_quantity');
            $table->dropColumn('reject_quantity');
        });
    }
};
