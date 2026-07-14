<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$p = App\Models\EcommerceProduct::first();
$response = app()->call('App\Http\Controllers\Api\Public\EcommerceProductController@show', ['slug' => $p->slug]);
echo json_encode($response->getData(true)['data'], JSON_PRETTY_PRINT);
