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
        Schema::table('product_bundles', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
            $table->decimal('sale_price', 15, 2)->nullable()->after('price');
            $table->text('description')->nullable()->after('sale_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bundles', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('sale_price');
            $table->dropColumn('description');
        });
    }
};
