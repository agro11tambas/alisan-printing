<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\EcommerceProduct;
use App\Services\EcommercePricingService;

class EcommerceProductController extends Controller
{
    public function __construct(private EcommercePricingService $pricingService)
    {
    }

    public function index()
    {
        $products = EcommerceProduct::with($this->relations())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Muat seluruh bundle sekaligus sebelum memformat. Tanpa ini setiap
        // kombinasi varian menembak query bundle-nya sendiri.
        $this->pricingService->preloadBundlesForPairs(
            $this->bundlePairs($products)
        );

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($product) => $this->formatProduct($product)),
        ]);
    }

    /**
     * Kumpulkan pasangan produk primary/secondary dari semua kombinasi varian.
     *
     * @param  \Illuminate\Support\Collection<int, EcommerceProduct>  $products
     * @return array<int, array{0: int, 1: int}>
     */
    private function bundlePairs($products): array
    {
        return $products
            ->flatMap(fn (EcommerceProduct $product) => $product->variantCombinations)
            ->map(function ($combination) {
                $primary = $combination->productOption?->product_id;
                $secondary = $combination->lidOption?->product_id;

                return $primary && $secondary ? [(int) $primary, (int) $secondary] : null;
            })
            ->filter()
            ->unique(fn (array $pair) => $pair[0].':'.$pair[1])
            ->values()
            ->all();
    }

    public function show($slug)
    {
        $product = EcommerceProduct::with($this->relations())
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // index() sudah melakukan ini, show() belum: tanpa preload, formatProduct()
        // menembak satu query bundle per kombinasi varian. Satu halaman detail
        // produk tercatat 480 query di log produksi karena baris ini tidak ada.
        $this->pricingService->preloadBundlesForPairs(
            $this->bundlePairs(collect([$product]))
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product),
        ]);
    }

    private function relations(): array
    {
        return [
            'categories',
            'unit',
            'galleryImages',
            'priceModes',
            'variantGroups.options.product.categories',
            'variantGroups.options.product.unitConversions.prices.priceMode',
            'variantCombinations.productOption.product',
            'variantCombinations.lidOption.product',
        ];
    }

    private function resolveImageUrl($path)
    {
        if (empty($path) || str_starts_with($path, 'http')) {
            return $path;
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('uploads/' . $path))) {
            return asset('uploads/' . $path);
        }

        return asset('storage/' . $path);
    }

    private function formatProduct(EcommerceProduct $product): array
    {
        $unitId = (int) $product->unit_id;
        $allowedModeSlugs = $product->priceModes->pluck('slug')->all();
        $defaultPrices = [];

        foreach ($product->variantGroups as $group) {
            foreach ($group->options as $option) {
                $modePrices = $option->product
                    ? $this->pricingService->productModePrices(
                        $option->product,
                        $unitId,
                        (float) $option->extra_price
                    )
                    : [];
                $modePrices = array_values(array_filter($modePrices,
                    fn ($price) => in_array($price['slug'], $allowedModeSlugs, true)
                ));
                $defaultPrice = $this->pricingService->defaultPrice($modePrices);
                $price = (float) ($defaultPrice['price'] ?? $option->price ?? 0);

                $option->setAttribute('mode_prices', $modePrices);
                $option->setAttribute('original_price', $price);
                $option->setAttribute('price', $price);
                $option->setAttribute('sale_price', null);

                if ($price > 0) {
                    $defaultPrices[] = $price;
                }
            }
        }

        foreach ($product->variantCombinations as $combination) {
            $primaryProductId = $combination->productOption?->product_id;
            $secondaryProductId = $combination->lidOption?->product_id;
            $bundle = $primaryProductId && $secondaryProductId
                ? $this->pricingService->bundleForPair((int) $primaryProductId, (int) $secondaryProductId)
                : null;
            $modePrices = $bundle
                ? $this->pricingService->bundleModePrices($bundle, $unitId)
                : [];
            $modePrices = array_values(array_filter($modePrices,
                fn ($price) => in_array($price['slug'], $allowedModeSlugs, true)
            ));
            $defaultPrice = $this->pricingService->defaultPrice($modePrices);
            $price = (float) ($defaultPrice['price'] ?? $combination->price ?? 0);

            $combination->setAttribute('mode_prices', $modePrices);
            $combination->setAttribute('original_price', $price);
            $combination->setAttribute('price', $price);
            $combination->setAttribute('sale_price', null);
        }

        if (!empty($defaultPrices)) {
            $product->setAttribute('price', min($defaultPrices));
        }

        $data = $product->toArray();

        if (!empty($data['main_image'])) {
            $data['main_image'] = $this->resolveImageUrl($data['main_image']);
        }
        if (!empty($data['main_video'])) {
            $data['main_video'] = $this->resolveImageUrl($data['main_video']);
        }

        // `$data[...] ?? []` menghasilkan salinan sementara, jadi iterasi harus
        // menulis balik lewat index ke `$data` — bukan lewat reference.
        foreach (array_keys($data['categories'] ?? []) as $categoryIndex) {
            $category = &$data['categories'][$categoryIndex];

            if (!empty($category['image'])) {
                $category['image'] = $this->resolveImageUrl($category['image']);
            }
        }
        unset($category);

        foreach (array_keys($data['gallery_images'] ?? []) as $galleryIndex) {
            $gallery = &$data['gallery_images'][$galleryIndex];

            if (!empty($gallery['image'])) {
                $gallery['image_url'] = $this->resolveImageUrl($gallery['image']);
            }
        }
        unset($gallery);

        foreach (array_keys($data['variant_groups'] ?? []) as $groupIndex) {
            foreach (array_keys($data['variant_groups'][$groupIndex]['options'] ?? []) as $optionIndex) {
                $option = &$data['variant_groups'][$groupIndex]['options'][$optionIndex];

                if (!empty($option['image'])) {
                    $option['image'] = $this->resolveImageUrl($option['image']);
                }
                if (!empty($option['video'])) {
                    $option['video'] = $this->resolveImageUrl($option['video']);
                }
                if (isset($option['product'])) {
                    $option['erp_product_id'] = $option['product']['id'];
                    $option['erp_category_ids'] = collect($option['product']['categories'] ?? [])->pluck('id')->all();
                }
                unset($option['product'], $option);
            }
        }

        foreach (array_keys($data['variant_combinations'] ?? []) as $variantIndex) {
            $variant = &$data['variant_combinations'][$variantIndex];

            if (!empty($variant['image'])) {
                $variant['image'] = $this->resolveImageUrl($variant['image']);
            }
            if (!empty($variant['video'])) {
                $variant['video'] = $this->resolveImageUrl($variant['video']);
            }
            unset($variant['product_option']['product'], $variant['lid_option']['product'], $variant);
        }

        return $data;
    }
}