<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialReport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\ProductionStock;
use App\Models\Purchase;
use App\Models\PurchaseEditHistory;
use App\Models\PurchaseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PurchaseListForceDeleteService
{
    /**
     * Remove a Purchase List child and reverse every effect it has posted.
     *
     * @return array<int, string> Files that can be removed after the DB transaction commits.
     */
    public function execute(Purchase $purchase): array
    {
        if ($purchase->status !== 'Purchase List' || ! $purchase->parent_purchase_id) {
            throw new RuntimeException('Force delete ini hanya untuk Purchase List anak dari Purchase Order.');
        }

        if ($purchase->purchaseReturn()->exists()) {
            throw new RuntimeException(
                'Purchase List tidak dapat di-force delete karena sudah memiliki Purchase Return.'
            );
        }

        $purchaseItems = PurchaseItem::withTrashed()
            ->where('purchase_id', $purchase->id)
            ->lockForUpdate()
            ->get();
        $purchaseItemIds = $purchaseItems->pluck('id');

        $inventories = Inventory::withTrashed()
            ->where('purchase_id', $purchase->id)
            ->lockForUpdate()
            ->get();
        $inventoryIds = $inventories->pluck('id');

        $inventoryItems = collect();
        if ($purchaseItemIds->isNotEmpty() || $inventoryIds->isNotEmpty()) {
            $inventoryItems = InventoryItem::withTrashed()
                ->where(function ($query) use ($purchaseItemIds, $inventoryIds) {
                    if ($purchaseItemIds->isNotEmpty()) {
                        $query->whereIn('purchase_item_id', $purchaseItemIds);
                    }

                    if ($inventoryIds->isNotEmpty()) {
                        $method = $purchaseItemIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('inventory_id', $inventoryIds);
                    }
                })
                ->lockForUpdate()
                ->get();
        }

        $this->reverseStock($purchase, $purchaseItems, $inventoryItems);

        $filesToDelete = $this->deleteStockInRecords(
            $inventoryIds,
            $inventoryItems->pluck('id')
        );
        $filesToDelete = array_merge(
            $filesToDelete,
            $this->reverseAndDeleteTransactions($purchase->id)
        );

        InventoryItem::withTrashed()
            ->whereIn('id', $inventoryItems->pluck('id'))
            ->forceDelete();
        Inventory::withTrashed()
            ->whereIn('id', $inventoryIds)
            ->forceDelete();

        PurchaseItem::withTrashed()
            ->whereIn('id', $purchaseItemIds)
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

        $purchase->forceDelete();

        return array_values(array_unique(array_filter($filesToDelete)));
    }

    private function reverseStock(
        Purchase $purchase,
        Collection $purchaseItems,
        Collection $inventoryItems
    ): void {
        foreach ($purchaseItems as $purchaseItem) {
            if (! $purchaseItem->product_id) {
                continue;
            }

            $linkedInventoryItems = $inventoryItems
                ->where('purchase_item_id', $purchaseItem->id);
            $receivedQty = max(0, (float) $linkedInventoryItems->sum('stock_in'));
            $orderedQty = max(
                0,
                (float) ($purchaseItem->qty_base
                    ?? ($purchaseItem->quantity * ($purchaseItem->unit_conversion_value ?: 1)))
            );
            $remainingIncoming = max(0, $orderedQty - $receivedQty);
            $firstInventoryItem = $linkedInventoryItems->first();

            if ($purchase->stock_destination === 'production') {
                $warehouseId = $purchaseItem->production_warehouse_id
                    ?? $firstInventoryItem?->production_warehouse_id
                    ?? 2;
                $stock = ProductionStock::where('product_id', $purchaseItem->product_id)
                    ->where('production_warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $this->logMissingStock($purchase, $purchaseItem, 'production', $receivedQty);
                    throw new RuntimeException(
                        "Stok production untuk product ID {$purchaseItem->product_id} tidak ditemukan. Force delete dibatalkan."
                    );
                }

                if ($receivedQty > 0) {
                    $stock->available_quantity = max(
                        0,
                        (float) $stock->available_quantity - $receivedQty
                    );
                }
                $stock->incoming_stock = max(
                    0,
                    (float) $stock->incoming_stock - $remainingIncoming
                );
                $stock->save();

                continue;
            }

            $warehouseId = $purchaseItem->inventory_warehouse_id
                ?? $firstInventoryItem?->inventory_warehouse_id
                ?? 1;
            $stock = InventoryStock::where('product_id', $purchaseItem->product_id)
                ->where('inventory_warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $this->logMissingStock($purchase, $purchaseItem, 'warehouse', $receivedQty);
                throw new RuntimeException(
                    "Stok inventory untuk product ID {$purchaseItem->product_id} tidak ditemukan. Force delete dibatalkan."
                );
            }

            if ($receivedQty > 0) {
                $stock->inventory_stock = max(
                    0,
                    (float) $stock->inventory_stock - $receivedQty
                );
                $stock->available_quantity = max(
                    0,
                    (float) $stock->available_quantity - $receivedQty
                );
                $stock->stock_after_sales = max(
                    0,
                    (float) $stock->stock_after_sales - $receivedQty
                );
            }
            $stock->incoming_stock = max(
                0,
                (float) $stock->incoming_stock - $remainingIncoming
            );
            $stock->save();
        }
    }

    private function deleteStockInRecords(
        Collection $inventoryIds,
        Collection $inventoryItemIds
    ): array {
        $historyHeaderIds = InventoryStockInHistory::withTrashed()
            ->when(
                $inventoryItemIds->isNotEmpty(),
                fn ($query) => $query->whereIn('inventory_item_id', $inventoryItemIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->pluck('inventory_stock_in_id');

        $stockIns = collect();

        if ($inventoryIds->isNotEmpty() || $historyHeaderIds->isNotEmpty()) {
            $stockIns = InventoryStockIn::withTrashed()
                ->where(function ($query) use ($inventoryIds, $historyHeaderIds) {
                    if ($inventoryIds->isNotEmpty()) {
                        $query->whereIn('inventory_id', $inventoryIds);
                    }

                    if ($historyHeaderIds->isNotEmpty()) {
                        $method = $inventoryIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('id', $historyHeaderIds);
                    }
                })
                ->lockForUpdate()
                ->get();
        }
        $stockInIds = $stockIns->pluck('id');

        if ($stockInIds->isNotEmpty() || $inventoryItemIds->isNotEmpty()) {
            InventoryStockInHistory::withTrashed()
                ->where(function ($query) use ($stockInIds, $inventoryItemIds) {
                    if ($stockInIds->isNotEmpty()) {
                        $query->whereIn('inventory_stock_in_id', $stockInIds);
                    }

                    if ($inventoryItemIds->isNotEmpty()) {
                        $method = $stockInIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('inventory_item_id', $inventoryItemIds);
                    }
                })
                ->forceDelete();
        }
        InventoryStockIn::withTrashed()
            ->whereIn('id', $stockInIds)
            ->forceDelete();

        return $stockIns
            ->pluck('waybill_image')
            ->filter()
            ->map(fn ($path) => public_path($path))
            ->values()
            ->all();
    }

    /**
     * Balikkan saldo akun lalu hapus permanen transaksi milik satu purchase.
     * Public karena dipakai juga oleh PurchaseOrderForceDeleteService.
     *
     * @return array<int, string> File bukti bayar yang bisa dihapus setelah commit.
     */
    public function reverseAndDeleteTransactions(int $purchaseId): array
    {
        $filesToDelete = [];
        $transactions = AccountTransaction::where('purchase_id', $purchaseId)
            ->lockForUpdate()
            ->get();

        foreach ($transactions as $transaction) {
            $account = Account::whereKey($transaction->account_id)
                ->lockForUpdate()
                ->first();

            if ($account) {
                $account->closing_balance =
                    (float) $account->closing_balance
                    - (float) $transaction->debit
                    + (float) $transaction->credit;
                $account->save();
            }

            $filesToDelete = array_merge(
                $filesToDelete,
                $this->extractProofFiles($transaction->proof)
            );
            $transaction->forceDelete();
        }

        $deletedTransactions = AccountTransaction::onlyTrashed()
            ->where('purchase_id', $purchaseId)
            ->get();
        foreach ($deletedTransactions as $transaction) {
            $filesToDelete = array_merge(
                $filesToDelete,
                $this->extractProofFiles($transaction->proof)
            );
            $transaction->forceDelete();
        }

        return $filesToDelete;
    }

    private function extractProofFiles(?string $proof): array
    {
        if (! $proof) {
            return [];
        }

        $decoded = json_decode($proof, true);
        if (! is_array($decoded)) {
            return [base_path($proof)];
        }

        return collect($decoded)
            ->map(fn ($item) => is_array($item) ? ($item['file'] ?? null) : $item)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn ($path) => base_path($path))
            ->values()
            ->all();
    }

    private function logMissingStock(
        Purchase $purchase,
        PurchaseItem $purchaseItem,
        string $destination,
        float $receivedQty
    ): void {
        Log::warning('Force delete Purchase List tidak menemukan row stok', [
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'product_id' => $purchaseItem->product_id,
            'destination' => $destination,
            'received_qty' => $receivedQty,
        ]);
    }
}
