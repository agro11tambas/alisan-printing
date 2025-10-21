<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_bundle_id',
        'status',
        'product_name',
        'satuan',
        'quantity',
        'completed_quantity',
        'price',
        'subtotal',
        'discount_price',
        'total_after_discount',
        'stock_out'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProgress(): HasMany
    {
        return $this->hasMany(OrderProgress::class, 'order_item_id');
    }

    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItemHistory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function productBundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'order_item_id');
    }

    public function components()
    {
        return $this->hasMany(OrderItemComponent::class, 'order_item_id');
    }

    public function designItems()
    {
        return $this->hasMany(DesignItem::class, 'order_item_id');
    }

    // public function getCompletedQuantityAttribute(): int
    // {
    //     return $this->orderProgress()->sum('completed_quantity');
    // }

    // public function getProgressPercentAttribute(): float
    // {
    //     return $this->quantity > 0
    //         ? round(($this->completed_quantity / $this->quantity) * 100, 2)
    //         : 0;
    // }

    protected static function booted()
    {
        static::restoring(function ($orderItem) {
            $orderItem->components()->withTrashed()->restore();
        });
    }
}
