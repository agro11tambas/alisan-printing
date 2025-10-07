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
        'verified_by',
        'verified_at',
        'note',
        'proof_delivery',
        'proof_waybill',
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
        return $this->belongsTo(User::class, 'driver_id');
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
        static::saved(function ($deliveryList) {
            $deliveryOrder = $deliveryList->deliveryOrder;
            if ($deliveryOrder) {
                // cek apakah semua delivery list pada DO ini sudah Finished
                $allFinished = $deliveryOrder->shipments()
                    ->where('status', '!=', 'Finished')
                    ->count() === 0;

                if ($allFinished) {
                    $deliveryOrder->status = 'Finished';
                    $deliveryOrder->saveQuietly(); // pakai saveQuietly biar ga trigger event lain
                }
            }
        });
    }
}
