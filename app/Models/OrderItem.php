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
        static::deleting(function ($orderItem) {
            if ($orderItem->isForceDeleting()) {
                // ✅ Force delete semua relasi anak
                $orderItem->components()->get()->each->forceDelete();
                $orderItem->designItems()->get()->each->forceDelete();
                $orderItem->deliveryItems()->get()->each->forceDelete();
                $orderItem->inventoryItems()->get()->each->forceDelete();
                // $orderItem->orderProgress()->get()->each->forceDelete();                

                // kalau nanti ada relasi tambahan lain, tinggal tambahkan di sini
            } else {
                // 💤 Soft delete semua relasi anak
                $orderItem->components()->get()->each->delete();
                $orderItem->designItems()->get()->each->delete();
                $orderItem->deliveryItems()->get()->each->delete();
                $orderItem->inventoryItems()->get()->each->delete();
                // $orderItem->orderProgress()->get()->each->delete();
            }
        });

        static::restoring(function ($orderItem) {
            // ♻️ Restore semua anak yang ikut soft delete
            $orderItem->components()->get()->each->restore();
            $orderItem->designItems()->get()->each->restore();
            $orderItem->deliveryItems()->get()->each->restore();
            $orderItem->inventoryItems()->get()->each->restore();
            // $orderItem->orderProgress()->get()->each->restore();
        });
    }
}
