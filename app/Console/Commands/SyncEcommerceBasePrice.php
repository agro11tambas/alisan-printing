<?php

namespace App\Console\Commands;

use App\Models\EcommerceProduct;
use App\Services\EcommercePricingService;
use Illuminate\Console\Command;

class SyncEcommerceBasePrice extends Command
{
    protected $signature = 'ecommerce:sync-base-price';

    protected $description = 'Hitung ulang base price semua ecommerce product dari harga termurah yang tampil di website';

    public function handle(EcommercePricingService $pricingService): int
    {
        $updated = 0;

        EcommerceProduct::with([
            'priceModes',
            'variantGroups.options.product.unitConversions.prices.priceMode',
        ])->chunk(50, function ($products) use ($pricingService, &$updated) {
            foreach ($products as $product) {
                $basePrice = $pricingService->basePriceFor($product);

                if ((float) $product->price === $basePrice) {
                    continue;
                }

                $this->line("#{$product->id} {$product->title}: {$product->price} -> {$basePrice}");
                $product->update(['price' => $basePrice]);
                $updated++;
            }
        });

        $this->info("Base price diperbarui untuk {$updated} produk.");

        return self::SUCCESS;
    }
}
