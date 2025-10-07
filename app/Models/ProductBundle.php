<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundle extends Model
{
    use HasFactory;

    protected $table = 'product_bundles';

    protected $fillable = [
        'name',
        'image',
        'sku',
        'price',
        'sale_price',
        'description',
    ];

    public function items()
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id')->with('product');
    }
}
