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
        Schema::table('delivery_item_histories', function (Blueprint $table) {
            $table->string('kurir')->nullable()->after('delivered_quantity');
            $table->string('note')->nullable()->after('kurir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_item_histories', function (Blueprint $table) {
            $table->dropColumn('kurir');
            $table->dropColumn('note');
        });
    }
};
