<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceVariantGroup extends Model
{
    protected $fillable = [
        'ecommerce_product_id',
        'name',
        'sort_order',
    ];

    public function ecommerceProduct()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function options()
    {
        return $this->hasMany(EcommerceVariantOption::class, 'variant_group_id')
            ->orderBy('sort_order');
    }
}
