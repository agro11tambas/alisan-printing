<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBundleUnitConversion extends Model
{
    protected $fillable = [
        'product_bundle_id',
        'unit_id',
        'conversion_value',
        'sale_price',
        'ratio_value',
    ];

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
