<?php

namespace App\Services;

use App\Models\MaterialRequest;
use Carbon\Carbon;

class MaterialRequestService
{
    /**
     * Generate nomor Material Request seperti RS/1/ALS/281025
     */
    public static function generateRequestNumber()
    {
        $today = Carbon::today();

        // Hitung jumlah request hari ini
        $countToday = MaterialRequest::whereDate('requested_at', $today)->count() + 1;

        // Format tanggal jadi DDMMYY
        $formattedDate = $today->format('dmy');

        return "RS/{$countToday}/ALS/{$formattedDate}";
    }
}
