<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_bundles';

    protected $fillable = [
        'name',
        'image',
        'sku',
        'price',
        'sale_price',
        'description',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id')->with('product');
    }

    public function products()
    {
        return $this->hasManyThrough(
            Products::class,
            ProductBundleItem::class,
            'bundle_id',
            'id',
            'id',
            'product_id'
        );
    }
}
