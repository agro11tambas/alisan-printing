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
            $table->foreignId('purchase_return_id')->nullable()->after('purchase_id')->constrained('purchase_returns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defect_products', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn('purchase_return_id');
        });
    }
};
