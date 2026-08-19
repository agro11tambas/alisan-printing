<?php

use App\Models\Purchase;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Aturan Completed berubah: selain Stock In penuh, seluruh Purchase List
     * anaknya juga harus lunas. PO yang belum memenuhi turun ke "Completed PL".
     */
    public function up(): void
    {
        Purchase::where('status', 'Purchase Orders')
            ->whereIn('approval_status', ['Approved', 'Partial', 'Completed PL', 'Completed'])
            ->chunkById(100, function ($orders) {
                $orders->each->syncApprovalProgress();
            });
    }

    public function down(): void
    {
        // Perhitungan ulang tidak punya kebalikan yang bermakna; status akan
        // menyesuaikan sendiri lewat syncApprovalProgress berikutnya.
    }
};
