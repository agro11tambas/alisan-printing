<?php

namespace App\Services;

use App\Models\PriceMode;
use App\Models\ProductBundle;
use App\Models\ProductBundleUnitConversion;
use Illuminate\Support\Collection;

class ProductBundlePricingService
{
    public function sync(ProductBundle $bundle): void
    {
        $bundle->load([
            'items.product.unitConversions.prices.priceMode',
            'items.product.baseUnit',
        ]);

        $items = $bundle->items
            ->filter(fn ($item) => $item->product)
            ->sortBy(fn ($item) => $item->role === 'primary' ? 0 : 1)
            ->values();

        if ($items->isEmpty()) {
            $bundle->unitConversions()->delete();
            $bundle->update(['price' => 0, 'sale_price' => 0, 'base_unit_id' => null]);
            return;
        }

        $polosanMode = PriceMode::where('slug', 'polosan')->first();
        $activeModeIds = PriceMode::active()->pluck('id')->map(fn ($id) => (int) $id);
        $unitIds = $items
            ->flatMap(fn ($item) => $item->product->unitConversions->pluck('unit_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $keptConversionIds = [];
        $bundlePricesByUnit = collect();

        foreach ($unitIds as $unitId) {
            $componentConversions = $items->map(function ($item) use ($unitId) {
                return [
                    'item' => $item,
                    'conversion' => $item->product->unitConversions->firstWhere('unit_id', $unitId),
                ];
            });
            $reference = $componentConversions->pluck('conversion')->filter()->first();
            if (! $reference) {
                continue;
            }

            $bundleConversion = ProductBundleUnitConversion::updateOrCreate(
                ['product_bundle_id' => $bundle->id, 'unit_id' => $unitId],
                [
                    'conversion_value' => $reference->conversion_value ?? 1,
                    'ratio_value' => $reference->ratio_value ?? $reference->conversion_value ?? 1,
                ]
            );
            $keptConversionIds[] = $bundleConversion->id;

            $modeIds = $componentConversions
                ->pluck('conversion')
                ->filter()
                ->flatMap(fn ($conversion) => $conversion->prices->pluck('price_mode_id'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $activeModeIds->contains($id))
                ->unique()
                ->values();

            if ($modeIds->isEmpty() && $polosanMode) {
                $modeIds = collect([(int) $polosanMode->id]);
            }

            $keptPriceIds = [];
            $pricesForUnit = collect();

            foreach ($modeIds as $modeId) {
                [$fixedCost, $margin] = $this->aggregateMode(
                    $componentConversions,
                    $modeId,
                    $polosanMode?->id
                );
                $salePrice = $fixedCost + $margin;

                $bundlePrice = $bundleConversion->prices()->updateOrCreate(
                    ['price_mode_id' => $modeId],
                    [
                        'fixed_cost' => $fixedCost,
                        'margin' => $margin,
                        'sale_price' => $salePrice,
                    ]
                );
                $keptPriceIds[] = $bundlePrice->id;
                $pricesForUnit->put($modeId, $salePrice);
            }

            $bundleConversion->prices()->whereNotIn('id', $keptPriceIds ?: [0])->delete();
            $legacyPrice = $polosanMode
                ? $pricesForUnit->get((int) $polosanMode->id)
                : null;
            $legacyPrice ??= $pricesForUnit->first() ?? 0;
            $bundleConversion->update(['sale_price' => $legacyPrice]);
            $bundlePricesByUnit->put($unitId, $legacyPrice);
        }

        $bundle->unitConversions()->whereNotIn('id', $keptConversionIds ?: [0])->delete();

        $baseUnitIds = $items->pluck('product.base_unit_id')->filter()->unique();
        $baseUnitId = $baseUnitIds->count() === 1 ? (int) $baseUnitIds->first() : null;
        $bundlePrice = $baseUnitId ? $bundlePricesByUnit->get($baseUnitId) : null;
        $bundlePrice ??= $bundlePricesByUnit->first() ?? 0;

        $bundle->update([
            'base_unit_id' => $baseUnitId,
            'price' => $bundlePrice,
            'sale_price' => $bundlePrice,
        ]);
    }

    private function aggregateMode(Collection $components, int $modeId, ?int $polosanModeId): array
    {
        $fixedCost = 0;
        $margin = 0;

        foreach ($components as $component) {
            $conversion = $component['conversion'];
            if (! $conversion) {
                continue;
            }

            $price = $conversion->prices->firstWhere('price_mode_id', $modeId);
            if (! $price && $polosanModeId) {
                $price = $conversion->prices->firstWhere('price_mode_id', $polosanModeId);
            }

            $quantity = (float) ($component['item']->quantity ?? 1);
            $fixedCost += (float) ($price?->fixed_cost ?? $conversion->fixed_cost ?? 0) * $quantity;
            $margin += (float) ($price?->margin ?? $conversion->margin ?? 0) * $quantity;
        }

        return [$fixedCost, $margin];
    }
}