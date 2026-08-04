<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_modes', function (Blueprint $table) {
            $table->string('production_flow', 30)->default('production')->after('slug');
        });

    }

    public function down(): void
    {
        Schema::table('price_modes', fn (Blueprint $table) => $table->dropColumn('production_flow'));
    }
};
