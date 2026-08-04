<?php

use App\Models\ProductBundle;
use App\Services\ProductBundlePricingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bundle_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_bundle_unit_conversion_id');
            $table->foreign('product_bundle_unit_conversion_id', 'bundle_unit_price_conversion_fk')
                ->references('id')
                ->on('product_bundle_unit_conversions')
                ->cascadeOnDelete();
            $table->foreignId('price_mode_id')->constrained('price_modes')->cascadeOnDelete();
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(
                ['product_bundle_unit_conversion_id', 'price_mode_id'],
                'bundle_unit_mode_unique'
            );
        });

        $service = app(ProductBundlePricingService::class);
        ProductBundle::query()->eachById(fn (ProductBundle $bundle) => $service->sync($bundle));
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundle_unit_prices');
    }
};