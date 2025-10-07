<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'order_id',
        'delivery_number',
        'delivery_date',
        'note',
        'status',
        'shipping_address',
        'created_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function shipments()
    {
        return $this->hasMany(DeliveryList::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::deleting(function ($deliveryOrder) {
            if ($deliveryOrder->isForceDeleting()) {
                foreach ($deliveryOrder->items as $item) {
                    $item->deliveryListItems()->forceDelete();
                    $item->forceDelete();
                }
                foreach ($deliveryOrder->shipments as $shipment) {
                    $shipment->items()->forceDelete();
                    $shipment->forceDelete();
                }
            } else {
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
