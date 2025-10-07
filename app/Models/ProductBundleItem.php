<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundleItem extends Model
{
    use HasFactory;

    protected $table = 'product_bundle_items';

    protected $fillable = [
        'product_id',
        'bundle_id',
        'quantity',
    ];
    
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id', 'id');
    }
}
