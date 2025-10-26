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
            $table->string('assign_code')->after('id')->unique()->nullable();
            $table->date('date')->after('operator_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropColumn('date');
            $table->dropColumn('assign_code');
        });
    }
};
