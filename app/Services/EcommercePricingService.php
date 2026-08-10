<?php

namespace App\Services;

use App\Models\EcommerceProduct;
use App\Models\PriceMode;
use App\Models\ProductBundle;
use App\Models\Products;

class EcommercePricingService
{
    private array $bundleCache = [];

    private ?PriceMode $fallbackModeMemo = null;

    private bool $fallbackModeResolved = false;

    /**
     * Muat semua bundle untuk sekumpulan pasangan produk sekaligus.
     *
     * bundleForPair() menembak satu query per pasangan, dan tiap query membawa
     * lima eager load. Halaman katalog toko yang berisi banyak kombinasi varian
     * jadi menghasilkan ratusan query untuk satu request.
     *
     * @param  array<int, array{0: int, 1: int}>  $pairs  daftar [id primary, id secondary]
     */
    public function preloadBundlesForPairs(array $pairs): void
    {
        $pending = array_filter(
            $pairs,
            fn (array $pair) => ! array_key_exists($pair[0].':'.$pair[1], $this->bundleCache)
        );

        if ($pending === []) {
            return;
        }

        $primaryIds = array_unique(array_column($pending, 0));
        $secondaryIds = array_unique(array_column($pending, 1));

        $bundles = ProductBundle::with([
            'items.product',
            'baseUnit',
            'unitConversions.prices.priceMode',
        ])
            ->whereHas('items', fn ($query) => $query
                ->where('role', 'primary')
                ->whereIn('product_id', $primaryIds))
            ->whereHas('items', fn ($query) => $query
                ->where('role', 'secondary')
                ->whereIn('product_id', $secondaryIds))
            ->get();

        foreach ($bundles as $bundle) {
            $primary = $bundle->items->firstWhere('role', 'primary');
            $secondary = $bundle->items->firstWhere('role', 'secondary');

            if ($primary && $secondary) {
                $this->bundleCache[$primary->product_id.':'.$secondary->product_id] = $bundle;
            }
        }

        // Pasangan yang memang tidak punya bundle ikut ditandai, supaya
        // bundleForPair() tidak menanyakannya lagi satu per satu.
        foreach ($pending as $pair) {
            $key = $pair[0].':'.$pair[1];

            if (! array_key_exists($key, $this->bundleCache)) {
                $this->bundleCache[$key] = null;
            }
        }
    }

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

    /**
     * Harga termurah yang benar-benar tampil di website untuk satu produk
     * ecommerce: harga default tiap opsi varian, dibatasi mode yang boleh dibeli.
     *
     * Nilainya 0 hanya kalau tidak ada satu pun opsi yang punya harga di mode
     * tersebut — bukan karena kolom price opsi belum terisi.
     */
    public function basePriceFor(EcommerceProduct $product): float
    {
        $product->loadMissing([
            'priceModes',
            'variantGroups.options.product.unitConversions.prices.priceMode',
        ]);

        $unitId = (int) $product->unit_id;
        $allowedModeIds = $product->priceModes->map(fn ($mode) => (int) $mode->id)->all();
        $prices = [];

        foreach ($product->variantGroups as $group) {
            foreach ($group->options as $option) {
                if (! $option->product) {
                    continue;
                }

                $modePrices = array_filter(
                    $this->productModePrices($option->product, $unitId, (float) $option->extra_price),
                    fn (array $modePrice) => in_array($modePrice['price_mode_id'], $allowedModeIds, true)
                );

                $price = (float) ($this->defaultPrice(array_values($modePrices))['price'] ?? 0);

                if ($price > 0) {
                    $prices[] = $price;
                }
            }
        }

        return $prices === [] ? 0.0 : min($prices);
    }

    private function selectMode(array $prices, string $mode): ?array
    {
        return collect($prices)->firstWhere('slug', $mode);
    }

    /**
     * Mode harga cadangan, ditanyakan sekali saja per request.
     *
     * legacyPrices() dipanggil untuk setiap opsi varian yang tidak punya harga
     * dinamis, dan sebelumnya setiap panggilan menembak query baru ke tabel
     * price_modes meski jawabannya selalu sama.
     */
    private function fallbackMode(): ?PriceMode
    {
        if ($this->fallbackModeResolved) {
            return $this->fallbackModeMemo;
        }

        $this->fallbackModeResolved = true;

        $this->fallbackModeMemo = PriceMode::query()
            ->where('slug', 'polosan')
            ->where('is_active', true)
            ->first()
            ?? PriceMode::query()->where('is_active', true)->orderBy('sort_order')->first();

        return $this->fallbackModeMemo;
    }

    private function legacyPrices(float $price, float $extraPrice = 0): array
    {
        if ($price <= 0 && $extraPrice <= 0) {
            return [];
        }

        $mode = $this->fallbackMode();

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