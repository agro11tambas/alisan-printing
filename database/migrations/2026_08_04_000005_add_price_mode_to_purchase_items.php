<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('price_mode_id')
                ->nullable()
                ->after('product_unit_conversion_id')
                ->constrained('price_modes')
                ->nullOnDelete();
            $table->string('mode')->nullable()->after('price_mode_id');
        });

        $defaultMode = DB::table('price_modes')
            ->where('slug', 'polosan')
            ->value('id') ?? DB::table('price_modes')->orderBy('sort_order')->value('id');

        if ($defaultMode) {
            $slug = DB::table('price_modes')->where('id', $defaultMode)->value('slug');
            DB::table('purchase_items')->whereNull('price_mode_id')->update([
                'price_mode_id' => $defaultMode,
                'mode' => $slug,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_mode_id');
            $table->dropColumn('mode');
        });
    }
};