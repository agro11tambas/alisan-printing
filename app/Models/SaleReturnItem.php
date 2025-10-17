<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturnItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'sale_return_id',
        'product_id',
        'product_bundle_id',
        'order_item_id',
        'quantity',
        'price',
        'avg_cost_at_return',
        'total_cost',
        'total',
        'reason',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relasi ke SaleReturn
    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    // Relasi ke Produk
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function productBundle()
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id');
    }
}
