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
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->foreignId('reject_product_id')->after('canceled_product_id')->nullable()->constrained('reject_products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_2', function (Blueprint $table) {
            $table->dropForeign(['reject_product_id']);
            $table->dropColumn('reject_product_id');
        });
    }
};
