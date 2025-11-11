<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryList extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'delivery_lists';

    protected $fillable = [
        'delivery_order_id',
        'driver_id',
        'shipment_number',
        'shipment_date',
        'driver',
        'vehicle',
        'status',
        'printed',
        'verified_by',
        'verified_at',
        'note',
        'proof_delivery',
        'proof_waybill',
        'proof_photos',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryListItem::class);
    }

    public function driverUser()
    {
        return $this->belongsTo(User::class, 'driver_id')->withTrashed();
    }

    protected static function booted()
    {
        static::deleting(function ($deliveryList) {
            if ($deliveryList->isForceDeleting()) {
                $deliveryList->items()->forceDelete();
            } else {
                $deliveryList->items()->delete();
            }
        });

        static::restoring(function ($deliveryList) {
            $deliveryList->items()->withTrashed()->restore();
        });

        // 🔎 Pantau setiap kali DeliveryList disimpan
        // Pantau setiap kali DeliveryList disimpan
        static::saved(function ($deliveryList) {
            $deliveryOrder = $deliveryList->deliveryOrder;

            if (!$deliveryOrder) {
                return;
            }

            // 🔹 Ambil semua item di DeliveryOrder
            $items = $deliveryOrder->items;

            if ($items->isEmpty()) {
                return;
            }

            // 🔹 Cek apakah semua item sudah dikirim penuh
            $allShipped = $items->every(function ($item) {
                $progressQty = (int) $item->progress_qty;
                $shippedQty  = (int) $item->shipped_qty;

                // hanya dianggap selesai jika progress_qty > 0 dan shipped_qty >= progress_qty
                return $progressQty > 0 && $shippedQty >= $progressQty;
            });

            // 🔹 Update status DeliveryOrder berdasarkan kondisi item
            if ($allShipped) {
                $deliveryOrder->status = 'Finished';
            } else {
                $deliveryOrder->status = 'Ongoing';
            }

            $deliveryOrder->saveQuietly();
        });
    }
}
