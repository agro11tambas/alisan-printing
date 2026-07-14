<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = App\Models\EcommerceProduct::with(['variantGroups.options.product', 'variantCombinations.productOption.product', 'variantCombinations.lidOption.product'])->first();
echo json_encode(app('App\Http\Controllers\Api\Public\EcommerceProductController')->show($p->slug)->getData(true), JSON_PRETTY_PRINT);
