<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundleItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_bundle_items';

    protected $fillable = [
        'product_id',
        'bundle_id',
        'quantity',
        'role',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id')->withTrashed();
    }

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id', 'id');
    }
}
