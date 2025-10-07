<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseProduct;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'inventory_warehouse_id',
        'status',
        'quantity',
        'price',
        'freight',
        'subtotal',
        'stock_in',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function scopeAccount($query)
    {
        return $query->where('status', 'Purchase Account');
    }

    public function scopeReturn($query)
    {
        return $query->where('status', 'Purchase Return');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'purchase_item_id');
    }

    public function inventoryReturnItems()
    {
        return $this->hasMany(InventoryItem::class, 'purchase_return_item_id');
    }
}
