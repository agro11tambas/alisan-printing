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
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->integer('canceled_quantity')->default(0)->after('quantity');
            $table->integer('defect_quantity')->default(0)->after('canceled_quantity');
            $table->integer('quantity')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('canceled_quantity');
            $table->dropColumn('defect_quantity');
            $table->integer('quantity')->change();
        });
    }
};
