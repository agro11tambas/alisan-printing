<?php

// Debug script to check discount matching
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get some variant options with their ERP product categories
$opts = \App\Models\EcommerceVariantOption::with('product.categories')->take(10)->get();
echo "=== VARIANT OPTIONS & THEIR ERP CATEGORIES ===\n";
foreach ($opts as $o) {
    if ($o->product) {
        $catIds = $o->product->categories->pluck('id')->implode(',');
        echo "OptionID={$o->id} | Alias={$o->alias} | ErpProduct={$o->product->name} (id={$o->product->id}) | ErpCategories=[{$catIds}]\n";
    } else {
        echo "OptionID={$o->id} | Alias={$o->alias} | NO ERP PRODUCT\n";
    }
}

echo "\n=== ACTIVE DISCOUNTS ===\n";
$discounts = \App\Models\Discount::with(['products', 'categories'])->where('is_active', 1)->get();
foreach ($discounts as $d) {
    $prodIds = $d->products->pluck('id')->implode(',');
    $catIds = $d->categories->pluck('id')->implode(',');
    echo "DiscountID={$d->id} | {$d->name} | type={$d->type} | amount={$d->amount} | apply_on={$d->apply_on} | min={$d->minimum_qty_or_amount} | products=[{$prodIds}] | categories=[{$catIds}]\n";
}
