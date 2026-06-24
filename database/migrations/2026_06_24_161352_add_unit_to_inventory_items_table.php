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
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->string('unit_name')->nullable()->after('product_id');
            $table->decimal('unit_conversion_value', 15, 4)->default(1)->after('unit_name');
            $table->decimal('qty_base', 15, 4)->default(0)->after('unit_conversion_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items_2', function (Blueprint $table) {
            $table->dropColumn('unit_name');
            $table->dropColumn('unit_conversion_value');
            $table->dropColumn('qty_base');
        });
    }
};
