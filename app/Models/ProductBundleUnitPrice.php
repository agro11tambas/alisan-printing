<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBundleUnitPrice extends Model
{
    protected $fillable = [
        'product_bundle_unit_conversion_id',
        'price_mode_id',
        'fixed_cost',
        'margin',
        'sale_price',
    ];

    protected $casts = [
        'fixed_cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function unitConversion()
    {
        return $this->belongsTo(ProductBundleUnitConversion::class, 'product_bundle_unit_conversion_id');
    }

    public function priceMode()
    {
        return $this->belongsTo(PriceMode::class);
    }
}