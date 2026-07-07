<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceVariantCombination extends Model
{
    protected $fillable = [
        'ecommerce_product_id',
        'product_option_id',
        'lid_option_id',
        'price', 'image',
        'video',
        'sort_order',
    ];

    public function ecommerceProduct()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function productOption()
    {
        return $this->belongsTo(EcommerceVariantOption::class, 'product_option_id');
    }

    public function lidOption()
    {
        return $this->belongsTo(EcommerceVariantOption::class, 'lid_option_id');
    }
}
