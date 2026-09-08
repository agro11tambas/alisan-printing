<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('mode')->index();
        });

        // Backfill order lama yang berasal dari checkout website: dibuat tanpa
        // user ERP (user_id null) tapi punya akun customer website.
        DB::table('orders')
            ->whereNull('user_id')
            ->whereNotNull('customer_account_id')
            ->update(['source' => 'website']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
