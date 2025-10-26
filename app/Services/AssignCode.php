<?php

namespace App\Services;

use App\Models\OrderProgressAssign;
use Carbon\Carbon;

class AssignCode
{
    public static function generateAssignCode()
    {
        $today = Carbon::now()->format('dmy'); // contoh: 231025
        $companyCode = 'ALS';

        // Hitung nomor urut untuk hari ini
        $last = OrderProgressAssign::whereDate('created_at', Carbon::today())->count() + 1;

        // Format nomor: ASG/{no_urut}/ALS/{tanggal}
        $assignCode = "ASG/{$last}/{$companyCode}/{$today}";

        // Pastikan unique (jaga-jaga kalau concurrency tinggi)
        while (OrderProgressAssign::where('assign_code', $assignCode)->exists()) {
            $last++;
            $assignCode = "ASG/{$last}/{$companyCode}/{$today}";
        }

        return $assignCode;
    }
}
