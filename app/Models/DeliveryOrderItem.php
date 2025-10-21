<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'delivery_order_items';

    protected $fillable = [
        'delivery_order_id',
        'order_progress_id',
        'order_item_id',
        'order_progress_item_id',
        'design_item_id',
        'product_id',
        'status',
        'progress_qty',
        'ready_qty',
        'shipped_qty',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function orderProgress()
    {
        return $this->belongsTo(OrderProgress::class, 'order_progress_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function deliveryListItems()
    {
        return $this->hasMany(DeliveryListItem::class, 'delivery_order_item_id');
    }

    public function orderProgressItem()
    {
        return $this->belongsTo(OrderProgressItem::class, 'order_progress_item_id');
    }

    protected static function booted()
    {
        static::deleting(function ($item) {
            if ($item->isForceDeleting()) {
                $item->deliveryListItems()->forceDelete();
            } else {
                $item->deliveryListItems()->delete();
            }
        });

        static::restoring(function ($item) {
            $item->deliveryListItems()->withTrashed()->restore();
        });
    }
}
