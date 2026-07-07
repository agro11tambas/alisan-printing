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
        'is_active',
    ];

    protected $appends = [
        'image_url',
        'video_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('uploads/' . $this->image) : null;
    }

    public function getVideoUrlAttribute()
    {
        return $this->video ? asset('uploads/' . $this->video) : null;
    }
}
