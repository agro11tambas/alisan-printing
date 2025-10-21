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
        Schema::table('defect_products', function (Blueprint $table) {
            $table->integer('eliminated_quantity')->default(0)->after('quantity');
            $table->integer('returned_quantity')->default(0)->after('eliminated_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defect_products', function (Blueprint $table) {
            $table->dropColumn('eliminated_quantity');
            $table->dropColumn('returned_quantity');
        });
    }
};
