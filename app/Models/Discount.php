<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;
use App\Models\ProductCategory;
use App\Models\EcommerceProductCategory;

class Discount extends Model
{
    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'type',
        'amount',
        'minimum_based_on',
        'minimum_qty_or_amount',
        'start_date',
        'end_date',
        'is_active',
        'apply_on',
        'apply_on_ecommerce',
    ];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'discount_products', 'discount_id', 'product_id');
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'discount_categories', 'discount_id', 'category_id');
    }

    public function ecommerceCategories()
    {
        return $this->belongsToMany(EcommerceProductCategory::class, 'discount_ecommerce_categories', 'discount_id', 'ecommerce_product_category_id');
    }
}
