<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchase_return_items';

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'inventory_warehouse_id',
        'purchase_item_id',
        'status',
        'quantity',
        'price',
        'freight',
        'total'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function purchaseItemReturn()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }
}
