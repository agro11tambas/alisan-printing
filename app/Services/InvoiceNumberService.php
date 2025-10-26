<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public static function generate(string $prefix, $orderDate)
    {
        $orderDate = Carbon::parse($orderDate);

        return DB::transaction(function () use ($prefix, $orderDate) {
            // Ambil order terakhir dengan prefix dan tanggal yang sama
            $lastOrder = Order::withTrashed()
                ->whereDate('order_date', $orderDate)
                ->where('order_number', 'like', $prefix . '/%/ALS/%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $lastSequence = 0;

            if ($lastOrder && preg_match('/' . $prefix . '\/(\d+)\/ALS/', $lastOrder->order_number, $matches)) {
                $lastSequence = (int) $matches[1];
            }

            // tambah 1 dari urutan terakhir
            $newSequence = $lastSequence + 1;

            // generate format baru
            $newNumber = sprintf(
                "%s/%d/ALS/%s",
                $prefix,
                $newSequence,
                $orderDate->format('dmy')
            );

            // pastikan nomor ini unik
            while (Order::withTrashed()->where('order_number', $newNumber)->exists()) {
                $newSequence++;
                $newNumber = sprintf(
                    "%s/%d/ALS/%s",
                    $prefix,
                    $newSequence,
                    $orderDate->format('dmy')
                );
            }

            return $newNumber;
        });
    }
}