<?php

use App\Models\Purchase;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * PO lama yang berstatus Completed dihitung ulang: yang belum stock in
     * penuh turun jadi "Completed PL", yang sudah tetap "Completed".
     */
    public function up(): void
    {
        Purchase::where('status', 'Purchase Orders')
            ->whereIn('approval_status', ['Approved', 'Partial', 'Completed'])
            ->chunkById(100, function ($orders) {
                $orders->each->syncApprovalProgress();
            });
    }

    public function down(): void
    {
        Purchase::where('status', 'Purchase Orders')
            ->where('approval_status', 'Completed PL')
            ->update(['approval_status' => 'Completed']);
    }
};
