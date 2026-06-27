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
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_account_id')
                ->nullable()
                ->after('customer_id');

            $table->foreign('customer_account_id')
                ->references('id')
                ->on('customer_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign(['customer_account_id']);
            $table->dropColumn('customer_account_id');
        });
    }
};
