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
        Schema::table('order_item_histories', function (Blueprint $table) {
            $table->renameColumn('quantity_change', 'old_quantity_change');
            $table->integer('new_quantity_change')->nullable()->after('old_quantity_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item_histories', function (Blueprint $table) {
            $table->renameColumn('old_quantity_change', 'quantity_change');
            $table->dropColumn('new_quantity_change');
        });
    }
};
