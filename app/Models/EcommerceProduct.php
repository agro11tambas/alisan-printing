<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'unit_id',
        'title',
        'slug',
        'brand',
        'main_image',
        'description',
        'multiple_qty',
        'min_qty',
        'max_qty',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(EcommerceProductCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
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

}
