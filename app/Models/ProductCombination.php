<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCombination extends Model
{
    protected $table = 'product_combinations';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'stock',
        'opening_stock',
        'opening_rate',
        'purchase_stock',
        'inventory_stock',
        'stock_after_sales',
        'avg_cost',
        'image',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function options()
    {
        return $this->belongsToMany(ProductVariantOption::class, 'product_combination_options', 'product_combination_id', 'product_variant_option_id')
            ->withPivot('product_combination_id', 'product_variant_option_id');
    }

    public function variantLabels()
    {
        return $this->options->map(function ($option) {
            return $option->value;
        })->implode(' / ');
    }
}
