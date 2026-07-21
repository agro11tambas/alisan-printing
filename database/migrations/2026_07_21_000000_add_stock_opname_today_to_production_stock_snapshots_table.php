<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_stock_snapshots', function (Blueprint $table) {
            $table->integer('stock_opname_today')->default(0)->after('assign_today');
        });
    }

    public function down(): void
    {
        Schema::table('production_stock_snapshots', function (Blueprint $table) {
            $table->dropColumn('stock_opname_today');
        });
    }
};
