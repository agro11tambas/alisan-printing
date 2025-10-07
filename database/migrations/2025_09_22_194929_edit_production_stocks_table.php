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
            $table->integer('canceled_product_stock')->after('finished_product_stock')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_stocks', function (Blueprint $table) {
            $table->dropColumn('canceled_product_stock');
        });
    }
};
