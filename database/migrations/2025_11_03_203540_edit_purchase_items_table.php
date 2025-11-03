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
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('price', 15, 5)->default(0)->change();
            $table->decimal('price_after_tax', 15, 5)->default(0)->change();
            $table->decimal('freight', 15, 5)->default(0)->change();
            $table->decimal('final_price', 15, 5)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->change();
            $table->decimal('price_after_tax', 12, 2)->default(0)->change();
            $table->decimal('freight', 12, 2)->default(0)->change();
            $table->decimal('final_price', 12, 2)->default(0)->change();
        });
    }
};
