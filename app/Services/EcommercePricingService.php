<?php

namespace App\Services;

use App\Models\PriceMode;
use App\Models\ProductBundle;
use App\Models\Products;

class EcommercePricingService
{
    private array $bundleCache = [];

    public function productModePrices(Products $product, int $unitId, float $extraPrice = 0): array
    {
        $conversion = $product->relationLoaded('unitConversions')
            ? $product->unitConversions->firstWhere('unit_id', $unitId)
            : $product->unitConversions()->with('prices.priceMode')->where('unit_id', $unitId)->first();

        if (!$conversion) {
            return $this->legacyPrices(
                (float) (($product->sale_price ?? 0) > 0 ? $product->sale_price : $product->price),
                $extraPrice
            );
        }

        if (!$conversion->relationLoaded('prices')) {
            $conversion->load('prices.priceMode');
        }

        $prices = $conversion->prices
            ->filter(fn ($price) => $price->priceMode?->is_active)
            ->sortBy(fn ($price) => $price->priceMode?->sort_order ?? PHP_INT_MAX)
            ->map(function ($price) use ($extraPrice) {
                $margin = (float) $price->margin + $extraPrice;

                return [
                    'price_mode_id' => (int) $price->price_mode_id,
                    'slug' => $price->priceMode->slug,
                    'name' => $price->priceMode->name,
                    'fixed_cost' => (float) $price->fixed_cost,
                    'margin' => $margin,
                    'price' => (float) $price->sale_price + $extraPrice,
                ];
            })
            ->values()
            ->all();

        if (!empty($prices)) {
            return $prices;
        }

        $legacyPrice = (float) (($conversion->sale_price ?? 0) > 0
            ? $conversion->sale_price
            : (($product->sale_price ?? 0) > 0 ? $product->sale_price : $product->price));

        return $this->legacyPrices($legacyPrice, $extraPrice);
    }

    public function bundleModePrices(ProductBundle $bundle, int $unitId): array
    {
        $conversion = $bundle->relationLoaded('unitConversions')
            ? $bundle->unitConversions->firstWhere('unit_id', $unitId)
            : $bundle->unitConversions()->with('prices.priceMode')->where('unit_id', $unitId)->first();

        if (!$conversion) {
            return $this->legacyPrices((float) (($bundle->sale_price ?? 0) > 0 ? $bundle->sale_price : $bundle->price));
        }

        if (!$conversion->relationLoaded('prices')) {
            $conversion->load('prices.priceMode');
        }

        $prices = $conversion->prices
            ->filter(fn ($price) => $price->priceMode?->is_active)
            ->sortBy(fn ($price) => $price->priceMode?->sort_order ?? PHP_INT_MAX)
            ->map(fn ($price) => [
                'price_mode_id' => (int) $price->price_mode_id,
                'slug' => $price->priceMode->slug,
                'name' => $price->priceMode->name,
                'fixed_cost' => (float) $price->fixed_cost,
                'margin' => (float) $price->margin,
                'price' => (float) $price->sale_price,
            ])
            ->values()
            ->all();

        if (!empty($prices)) {
            return $prices;
        }

        return $this->legacyPrices((float) (($conversion->sale_price ?? 0) > 0
            ? $conversion->sale_price
            : (($bundle->sale_price ?? 0) > 0 ? $bundle->sale_price : $bundle->price)));
    }

    public function productPrice(Products $product, int $unitId, string $mode, float $extraPrice = 0): ?array
    {
        return $this->selectMode($this->productModePrices($product, $unitId, $extraPrice), $mode);
    }

    public function bundlePrice(ProductBundle $bundle, int $unitId, string $mode): ?array
    {
        return $this->selectMode($this->bundleModePrices($bundle, $unitId), $mode);
    }

    public function bundleForPair(int $primaryProductId, int $secondaryProductId): ?ProductBundle
    {
        $key = $primaryProductId . ':' . $secondaryProductId;

        if (!array_key_exists($key, $this->bundleCache)) {
            $this->bundleCache[$key] = ProductBundle::with([
                'items.product',
                'baseUnit',
                'unitConversions.prices.priceMode',
            ])
                ->whereHas('items', fn ($query) => $query
                    ->where('role', 'primary')
                    ->where('product_id', $primaryProductId))
                ->whereHas('items', fn ($query) => $query
                    ->where('role', 'secondary')
                    ->where('product_id', $secondaryProductId))
                ->first();
        }

        return $this->bundleCache[$key];
    }

    public function defaultPrice(array $prices): ?array
    {
        return collect($prices)->firstWhere('slug', 'polosan') ?? ($prices[0] ?? null);
    }

    private function selectMode(array $prices, string $mode): ?array
    {
        return collect($prices)->firstWhere('slug', $mode);
    }

    private function legacyPrices(float $price, float $extraPrice = 0): array
    {
        if ($price <= 0 && $extraPrice <= 0) {
            return [];
        }

        $mode = PriceMode::query()
            ->where('slug', 'polosan')
            ->where('is_active', true)
            ->first()
            ?? PriceMode::query()->where('is_active', true)->orderBy('sort_order')->first();

        if (!$mode) {
            return [];
        }

        return [[
            'price_mode_id' => (int) $mode->id,
            'slug' => $mode->slug,
            'name' => $mode->name,
            'fixed_cost' => 0.0,
            'margin' => $price + $extraPrice,
            'price' => $price + $extraPrice,
        ]];
    }
}