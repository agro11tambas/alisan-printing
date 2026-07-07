<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceVariantOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'variant_group_id',
        'product_id',
        'price', 'alias',
        'extra_price',
        'image',
        'video',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(EcommerceVariantGroup::class, 'variant_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }
}
