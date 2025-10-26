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
            $table->foreignId('order_progress_assign_id')->after('order_progress_item_id')->nullable()->constrained('order_progress_assigns');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            $table->dropForeign(['order_progress_assign_id']);
            $table->dropColumn('order_progress_assign_id');
        });
    }
};
