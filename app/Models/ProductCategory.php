<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;
use App\Models\Discount;

class ProductCategory extends Model
{
    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent',
    ];

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'discount_categories', 'category_id', 'discount_id');
    }

    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_category_product', 'category_id', 'product_id');
    }
}
