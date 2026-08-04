<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_conversion_id')
                ->constrained('product_unit_conversions')
                ->cascadeOnDelete();
            $table->string('mode', 30);
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_unit_conversion_id', 'mode']);
        });

        $now = now();
        DB::table('product_unit_conversions')
            ->select(['id', 'fixed_cost', 'margin', 'sale_price'])
            ->orderBy('id')
            ->chunkById(500, function ($conversions) use ($now) {
                DB::table('product_unit_prices')->insert($conversions->map(fn ($conversion) => [
                    'product_unit_conversion_id' => $conversion->id,
                    'mode' => 'polosan',
                    'fixed_cost' => $conversion->fixed_cost ?? 0,
                    'margin' => $conversion->margin ?? 0,
                    'sale_price' => $conversion->sale_price ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_prices');
    }
};
