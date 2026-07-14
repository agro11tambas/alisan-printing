<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceProductImage extends Model
{
    protected $fillable = [
        'ecommerce_product_id',
        'image',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
    ];

    public function ecommerceProduct()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('uploads/' . $this->image) : null;
    }
}
