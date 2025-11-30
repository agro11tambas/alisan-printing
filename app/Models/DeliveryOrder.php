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
        'design_id',
        'delivery_number',
        'delivery_date',
        'note',
        'status',
        'customer',
        'shipping_address',
        'google_map_link',
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
        return $this->hasMany(DeliveryOrderItem::class, 'delivery_order_id');
    }

    public function shipments()
    {
        return $this->hasMany(DeliveryList::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
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

    public function refreshStatus(): void
    {
        // pastikan relasi ke-load
        $this->loadMissing(['items', 'shipments']);

        // Kalau tidak ada item, anggap belum selesai
        if ($this->items->isEmpty()) {
            $this->status = 'Ongoing';
            $this->saveQuietly();
            return;
        }

        // ✅ 1) Semua DeliveryList untuk DO ini harus status = 'Finished'
        $allShipmentsFinished = $this->shipments->isNotEmpty()
            && $this->shipments->every(function ($shipment) {
                return $shipment->status === 'Finished';
            });

        // ✅ 2) Untuk setiap item: ready_qty >= shipped_qty
        // $qtyOk = $this->items->every(function ($row) {
        //     $readyQty   = (int) $row->ready_qty;
        //     $shippedQty = (int) $row->shipped_qty;

        //     return $shippedQty >= $readyQty;
        // });

        $qtyOk = $this->items->every(function ($row) {

            $orderItem = $row->orderItem;

            if (!$orderItem) {
                return false;
            }

            // 1) Kalau produk punya komponen → qty target = total qty komponen
            $componentQty = (int) $orderItem->components->sum('qty');

            if ($componentQty > 0) {
                $targetQty = $componentQty;
            } else {
                // 2) Produk tanpa komponen → fallback ke qty order item
                $targetQty = (int) $orderItem->quantity;
            }

            // Qty yang sudah dikirim
            $shippedQty = (int) $row->shipped_qty;

            return $shippedQty >= $targetQty;
        });

        $this->status = ($allShipmentsFinished && $qtyOk) ? 'Finished' : 'Ongoing';
        $this->saveQuietly();
    }
}
