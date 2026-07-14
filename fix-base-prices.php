<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\EcommerceProduct::all();
$updated = 0;
foreach ($products as $product) {
    $minPrice = 0;
    $firstGroup = $product->variantGroups()->first();
    if ($firstGroup) {
        $optionPrices = \App\Models\EcommerceVariantOption::where('variant_group_id', $firstGroup->id)
            ->pluck('price')
            ->filter(function($price) { return $price > 0; });
        if ($optionPrices->isNotEmpty()) {
            $minPrice = $optionPrices->min();
        }
    }
    $product->update(['price' => $minPrice]);
    $updated++;
}
echo "Updated $updated products.\n";
