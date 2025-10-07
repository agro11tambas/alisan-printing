<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;

class ProductTag extends Model
{
    protected $table = 'product_tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent',
        'woocommerce_id',
    ];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_tag_product', 'tag_id', 'product_id');
    }
}
