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
        Schema::table('opening_balances', function (Blueprint $table) {
            $table->string('account')->after('amount');
            $table->dropColumn('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opening_balances', function (Blueprint $table) {
            $table->dropColumn('account');
            $table->string('transaction_type')->after('amount');
        });
    }
};
