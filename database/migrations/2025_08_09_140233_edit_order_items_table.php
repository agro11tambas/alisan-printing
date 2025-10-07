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
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_bundle_id')->nullable()->after('product_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_bundle_id')->references('id')->on('product_bundles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_bundle_id']);
            $table->dropColumn('product_bundle_id');
        });
    }
};
