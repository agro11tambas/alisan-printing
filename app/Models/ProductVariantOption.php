<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantOption extends Model
{
    protected $table = 'product_variant_options';

    protected $fillable = [
        'product_variant_id',
        'value',
        'price',
        'opening_stock',
        'opening_rate',
        'purchase_stock',
        'inventory_stock',
        'stock_after_sales',
        'avg_cost',
    ];

    public function combinations()
    {
        return $this->belongsToMany(ProductCombination::class, 'product_combination_options', 'product_variant_option_id', 'product_combination_id');
    }
}
