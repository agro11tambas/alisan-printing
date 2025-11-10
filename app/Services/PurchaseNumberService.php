<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseNumberService
{
    /**
     * Generate unique purchase number in format:
     * PO/{sequence}/ALS/{ddmmyy}
     *
     * @param  string|\DateTime  $purchaseDate
     * @return string
     */
    public static function generate($purchaseDate)
    {
        $purchaseDate = Carbon::parse($purchaseDate);

        return DB::transaction(function () use ($purchaseDate) {
            // Ambil purchase terakhir dengan tanggal yang sama
            $lastPurchase = Purchase::withTrashed()
                ->whereDate('purchase_date', $purchaseDate)
                ->where('purchase_number', 'like', 'PO/%/ALS/%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $lastSequence = 0;

            if ($lastPurchase && preg_match('/PO\/(\d+)\/ALS/', $lastPurchase->purchase_number, $matches)) {
                $lastSequence = (int) $matches[1];
            }

            // tambah 1 dari urutan terakhir
            $newSequence = $lastSequence + 1;

            // generate format baru
            $newNumber = sprintf(
                "PO/%d/ALS/%s",
                $newSequence,
                $purchaseDate->format('dmy')
            );

            // pastikan nomor ini unik (kalau sebelumnya rollback atau race condition)
            while (Purchase::withTrashed()->where('purchase_number', $newNumber)->exists()) {
                $newSequence++;
                $newNumber = sprintf(
                    "PO/%d/ALS/%s",
                    $newSequence,
                    $purchaseDate->format('dmy')
                );
            }

            return $newNumber;
        });
    }
}
