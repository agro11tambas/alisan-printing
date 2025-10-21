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
        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->foreignId('design_item_id')->nullable()->after('order_progress_id')->constrained('design_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_items', function (Blueprint $table) {
            $table->dropForeign(['design_item_id']);
            $table->dropColumn('design_item_id');
        });
    }
};
