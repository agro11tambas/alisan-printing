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
            $table->string('proof_delivery')->nullable()->after('note'); // foto barang diterima
            $table->string('proof_waybill')->nullable()->after('proof_delivery'); // foto surat jalan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_lists', function (Blueprint $table) {
            $table->dropColumn('proof_delivery');
            $table->dropColumn('proof_waybill');
        });
    }
};
