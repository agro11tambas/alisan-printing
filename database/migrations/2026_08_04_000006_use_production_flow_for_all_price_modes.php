<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('price_modes') && Schema::hasColumn('price_modes', 'production_flow')) {
            DB::table('price_modes')->update(['production_flow' => 'production']);
        }
    }

    public function down(): void
    {
        // Semua mode tetap menggunakan alur production.
    }
};