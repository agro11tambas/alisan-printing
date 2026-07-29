<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sale_unit_id')->nullable()->after('base_unit_id')
                ->constrained('product_units')->nullOnDelete();
            $table->foreignId('purchase_unit_id')->nullable()->after('sale_unit_id')
                ->constrained('product_units')->nullOnDelete();
        });

        DB::table('products')->select(['id', 'base_unit_id'])->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $units = DB::table('product_unit_conversions')
                        ->where('product_id', $product->id)
                        ->select(['unit_id', 'conversion_value', 'ratio_value'])
                        ->get();

                    $saleUnitId = $units
                        ->sortByDesc(fn ($unit) => (float) ($unit->ratio_value ?? 0))
                        ->first()?->unit_id ?? $product->base_unit_id;

                    $purchaseUnitId = $units
                        ->first(fn ($unit) => abs((float) ($unit->ratio_value ?? 0) - 1) < 0.0001)
                        ?->unit_id;

                    $purchaseUnitId ??= $units
                        ->sortByDesc(fn ($unit) => (float) ($unit->conversion_value ?? 0))
                        ->first()?->unit_id ?? $product->base_unit_id;

                    DB::table('products')->where('id', $product->id)->update([
                        'sale_unit_id' => $saleUnitId,
                        'purchase_unit_id' => $purchaseUnitId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sale_unit_id']);
            $table->dropForeign(['purchase_unit_id']);
            $table->dropColumn(['sale_unit_id', 'purchase_unit_id']);
        });
    }
};
