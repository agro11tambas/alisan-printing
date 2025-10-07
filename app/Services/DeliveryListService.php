<?php

namespace App\Services;

use App\Models\DeliveryList;
use App\Models\DeliveryOrder;
use Carbon\Carbon;

class DeliveryListService
{
    public static function generateShipmentNumber(DeliveryOrder $deliveryOrder)
    {
        $today = Carbon::now()->format('dmy'); // 260925

        // nomor urut per hari
        $last = DeliveryList::whereDate('created_at', Carbon::today())->count() + 1;

        // ambil format dari delivery order
        // contoh: DO/INV/1/ALS/260925
        $parts = explode('/', $deliveryOrder->delivery_number);

        // ganti DO -> DL
        $parts[0] = 'DL';

        // ganti nomor urut
        $parts[2] = $last;

        // ganti tanggal
        $parts[4] = $today;

        return implode('/', $parts); // DL/INV/1/ALS/260925
    }
}