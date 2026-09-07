<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\ProductBundle;
use App\Models\Products;
use Closure;

/**
 * Satu-satunya tempat isi blob katalog ERP dibangun.
 *
 * Sebelumnya penyusunnya hanya ada di dalam SaleListController::create(), jadi
 * endpoint yang menyajikan katalog sebagai file .js tidak punya cara membangun
 * blob yang hilang -- ia cuma bisa menyuruh browser reload sekali. Kalau reload
 * itu tidak menolong, file .js yang terkirim tidak mendefinisikan
 * window.__erpCatalog sama sekali, skrip halaman input order melempar
 * TypeError di baris pertamanya, dan SELURUH tombol di halaman itu mati tanpa
 * pesan apa pun.
 *
 * Dengan penyusunnya di sini, endpoint .js bisa membangun sendiri. Request itu
 * jadi lambat sekali-sekali; itu jauh lebih murah daripada halaman mati.
 */
class ErpCatalogBlobs
{
    /** Slug URL aset .js -> kunci blob di cache. */
    public const PETA_SLUG = [
        'sale-list-create-products' => 'sale-list:create:products',
        'sale-list-create-bundles' => 'sale-list:create:bundles',
    ];

    /**
     * Penyusun isi blob per kunci.
     *
     * @return (Closure(): mixed)|null
     */
    public function builder(string $key): ?Closure
    {
        return match ($key) {
            'sale-list:create:products' => fn () => $this->products(),
            'sale-list:create:bundles' => fn () => $this->bundles(),
            default => null,
        };
    }

    /**
     * Ambil blob dari cache, bangun kalau belum ada.
     */
    public function json(string $key): ?string
    {
        $builder = $this->builder($key);

        if ($builder === null) {
            return null;
        }

        return app(ErpCatalogPayload::class)->json($key, $builder);
    }

    private function products(): array
    {
        $products = Products::query()
            ->select([
                'id',
                'name',
                'sku',
                'price',
                'sale_price',
                'base_unit_id',
                'sale_unit_id',
            ])
            ->with([
                'discounts',
                ...Discount::payloadRelations('discounts.'),
                'categories.discounts',
                ...Discount::payloadRelations('categories.discounts.'),
                'categories:id',
                'unitConversions:id,product_id,unit_id,conversion_value,sale_price',
                'unitConversions.unit:id,name',
                'unitConversions.prices:id,product_unit_conversion_id,price_mode_id,fixed_cost,margin,sale_price',
                'unitConversions.prices.priceMode:id,name,slug',
            ])
            ->orderBy('name', 'asc')
            ->get();

        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'base_unit_id' => $product->base_unit_id,
                'sale_unit_id' => $product->sale_unit_id,
                'discounts' => $product->discounts->map(fn ($discount) => $discount->toDiscountPayload())->values()->toArray(),
                'categories' => $product->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'discounts' => $category->discounts->map(fn ($discount) => $discount->toDiscountPayload())->values()->toArray(),
                    ];
                })->toArray(),
                'units' => $product->unitConversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'unit_id' => $conversion->unit_id,
                        'unit_name' => optional($conversion->unit)->name,
                        'conversion_value' => $conversion->conversion_value,
                        'sale_price' => $conversion->sale_price,
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];
        })->toArray();
    }

    private function bundles(): array
    {
        // Form-nya hanya butuh keanggotaan bundle, bukan seluruh graf harga.
        $productBundles = ProductBundle::query()
            ->select(['id', 'base_unit_id', 'price'])
            ->with([
                'primaryItem:id,bundle_id,product_id,role',
                'secondaryItems:id,bundle_id,product_id,role',
                'secondaryItems.product:id,name,sku',
                'unitConversions:id,product_bundle_id,unit_id,conversion_value,sale_price',
                'unitConversions.unit:id,name',
                'unitConversions.prices.priceMode',
            ])
            ->get();

        return $productBundles->map(function ($bundle) {
            return [
                'id' => $bundle->id,
                'base_unit_id' => $bundle->base_unit_id,
                'price' => $bundle->price,
                'units' => $bundle->unitConversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'unit_id' => $conversion->unit_id,
                        'unit_name' => optional($conversion->unit)->name,
                        'conversion_value' => $conversion->conversion_value,
                        'sale_price' => $conversion->sale_price,
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
                    ];
                })->values()->toArray(),
                'primary_item' => $bundle->primaryItem ? [
                    'product_id' => $bundle->primaryItem->product_id,
                ] : null,
                'secondary_items' => $bundle->secondaryItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                        ] : null,
                    ];
                })->values()->toArray(),
            ];
        })->toArray();
    }
}
