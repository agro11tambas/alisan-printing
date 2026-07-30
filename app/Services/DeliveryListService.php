<?php

namespace App\Services;

use App\Models\DeliveryList;
use App\Models\DeliveryOrder;
use Carbon\Carbon;

class DeliveryListService
{
    public static function generateShipmentNumber(DeliveryOrder $deliveryOrder)
    {
        $today = Carbon::now()->format('dmy'); // contoh: 260925

        // Hitung nomor urut untuk hari ini
        $last = DeliveryList::withTrashed()
            ->whereDate('created_at', Carbon::today())
            ->count() + 1;

        // Format nomor: DO/{no_urut}/ALS/{tanggal}
        $shipmentNumber = "DO/{$last}/ALS/{$today}";

        // Pastikan unique (jaga-jaga kalau concurrency tinggi)
        while (DeliveryList::withTrashed()->where('shipment_number', $shipmentNumber)->exists()) {
            $last++;
            $shipmentNumber = "DO/{$last}/ALS/{$today}";
        }

        return $shipmentNumber;
    }
}
