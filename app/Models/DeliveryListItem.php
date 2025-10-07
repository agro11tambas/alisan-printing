<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryListItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'delivery_list_items';

    protected $fillable = [
        'delivery_list_id',
        'delivery_order_item_id',
        'product_id',
        'shipped_quantity',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(DeliveryList::class, 'delivery_list_id');
    }

    public function deliveryOrderItem()
    {
        return $this->belongsTo(DeliveryOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    protected static function booted()
    {
        // 🔥 Delete (soft delete / force delete)
        static::deleting(function ($deliveryOrder) {
            if ($deliveryOrder->isForceDeleting()) {
                // Hapus permanent semua child
                foreach ($deliveryOrder->items as $item) {
                    $item->deliveryListItems()->forceDelete();
                    $item->forceDelete();
                }
                foreach ($deliveryOrder->shipments as $shipment) {
                    $shipment->items()->forceDelete();
                    $shipment->forceDelete();
                }
            } else {
                // Soft delete semua child
                foreach ($deliveryOrder->items as $item) {
                    $item->deliveryListItems()->delete();
                    $item->delete();
                }
                foreach ($deliveryOrder->shipments as $shipment) {
                    $shipment->items()->delete();
                    $shipment->delete();
                }
            }
        });

        // 🔥 Restore
        static::restoring(function ($deliveryOrder) {
            $deliveryOrder->items()->withTrashed()->restore();
            $deliveryOrder->shipments()->withTrashed()->restore();

            foreach ($deliveryOrder->items()->withTrashed()->get() as $item) {
                $item->deliveryListItems()->withTrashed()->restore();
            }

            foreach ($deliveryOrder->shipments()->withTrashed()->get() as $shipment) {
                $shipment->items()->withTrashed()->restore();
            }
        });
    }
}
