<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'price', 'title',
        'slug',
        'brand',
        'main_image',
        'main_video',
        'description',
        'multiple_qty',
        'min_qty',
        'max_qty',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'image',
        'video'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(EcommerceProductCategory::class, 'ecommerce_product_category_pivot', 'ecommerce_product_id', 'ecommerce_product_category_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
    public function priceModes()
    {
        return $this->belongsToMany(
            PriceMode::class,
            'ecommerce_product_price_modes',
            'ecommerce_product_id',
            'price_mode_id'
        )->withTimestamps()->orderBy('sort_order');
    }

    public function galleryImages()
    {
        return $this->hasMany(EcommerceProductImage::class, 'ecommerce_product_id')->orderBy('sort_order');
    }

    public function variantGroups()
    {
        return $this->hasMany(EcommerceVariantGroup::class, 'ecommerce_product_id')
            ->orderBy('sort_order');
    }

    public function variantOptions()
    {
        return $this->hasManyThrough(
            EcommerceVariantOption::class,
            EcommerceVariantGroup::class,
            'ecommerce_product_id',
            'variant_group_id',
            'id',
            'id'
        );
    }

    public function variantCombinations()
    {
        return $this->hasMany(EcommerceVariantCombination::class, 'ecommerce_product_id')
            ->orderBy('sort_order');
    }

    public function getImageAttribute()
    {
        return $this->main_image ? asset('uploads/' . $this->main_image) : null;
    }

    public function getVideoAttribute()
    {
        return $this->main_video ? asset('uploads/' . $this->main_video) : null;
    }
}
