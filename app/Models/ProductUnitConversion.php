<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnitConversion extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_value',
        'ratio_value',
        'sale_price',
        'purchase_price',
        'fixed_cost',
        'margin',
    ];

    protected $casts = [
        'conversion_value' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'fixed_cost' => 'decimal:2',
        'margin' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
