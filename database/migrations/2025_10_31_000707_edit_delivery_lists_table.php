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
        Schema::table('delivery_lists', function (Blueprint $table) {
            $table->string('proof_photos')->nullable()->after('proof_waybill');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_lists', function (Blueprint $table) {
            $table->dropColumn('proof_photos');
        });
    }
};
