<?php

namespace App\Services;

use App\Models\FinancialReport;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\PurchaseEditHistory;
use App\Models\PurchaseItem;
use RuntimeException;

class PurchaseOrderForceDeleteService
{
    public function __construct(
        private PurchaseListForceDeleteService $purchaseListForceDeleteService
    ) {}

    /**
     * Hapus permanen sebuah Purchase Order beserta seluruh Purchase List anaknya.
     *
     * Tiap Purchase List dihapus lewat PurchaseListForceDeleteService supaya
     * stok, stock-in, inventory, dan transaksi akunnya ikut dibalik.
     *
     * @return array<int, string> File yang bisa dihapus setelah transaksi commit.
     */
    public function execute(Purchase $purchase): array
    {
        if ($purchase->status !== 'Purchase Orders') {
            throw new RuntimeException('Force delete ini hanya untuk Purchase Order.');
        }

        $filesToDelete = [];

        // Purchase List anak, termasuk yang sudah di-soft delete.
        $purchaseLists = Purchase::withTrashed()
            ->where('parent_purchase_id', $purchase->id)
            ->lockForUpdate()
            ->get();

        foreach ($purchaseLists as $purchaseList) {
            try {
                $filesToDelete = array_merge(
                    $filesToDelete,
                    $this->purchaseListForceDeleteService->execute($purchaseList)
                );
            } catch (RuntimeException $e) {
                // Sebutkan PL mana yang menggagalkan supaya jelas di layar.
                throw new RuntimeException(
                    'Purchase List '.($purchaseList->purchase_number ?? $purchaseList->id).': '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }

        // PO tidak pernah memegang inventory sendiri. Kalau ada, datanya anomali
        // dan stoknya tidak boleh dihapus diam-diam.
        $orphanInventories = Inventory::withTrashed()
            ->where('purchase_id', $purchase->id)
            ->count();

        if ($orphanInventories > 0) {
            throw new RuntimeException(
                'Purchase Order ini masih memiliki data inventory sendiri. Force delete dibatalkan agar stok tidak melenceng.'
            );
        }

        $filesToDelete = array_merge(
            $filesToDelete,
            $this->purchaseListForceDeleteService->reverseAndDeleteTransactions($purchase->id)
        );

        PurchaseItem::withTrashed()
            ->where('purchase_id', $purchase->id)
            ->forceDelete();
        PurchaseEditHistory::withTrashed()
            ->where('purchase_id', $purchase->id)
            ->forceDelete();
        FinancialReport::withTrashed()
            ->where('reference_table', 'purchases')
            ->where('reference_id', $purchase->id)
            ->forceDelete();

        if ($purchase->image) {
            $filesToDelete[] = public_path('storage/'.$purchase->image);
        }

        if ($purchase->waybill_image) {
            $filesToDelete[] = public_path($purchase->waybill_image);
        }

        $purchase->forceDelete();

        return array_values(array_unique(array_filter($filesToDelete)));
    }
}
