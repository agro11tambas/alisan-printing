<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $items = DB::table('purchase_items as purchase_item')
                ->join('product_unit_conversions as conversion', 'conversion.id', '=', 'purchase_item.product_unit_conversion_id')
                ->join('purchases as purchase', 'purchase.id', '=', 'purchase_item.purchase_id')
                ->whereNull('purchase_item.deleted_at')
                ->whereRaw('ABS(COALESCE(purchase_item.unit_conversion_value, 1) - COALESCE(conversion.conversion_value, 1)) > 0.0001')
                ->select([
                    'purchase_item.id',
                    'purchase_item.product_id',
                    'purchase_item.quantity',
                    'purchase_item.unit_conversion_value',
                    'purchase_item.stock_in',
                    'conversion.conversion_value as correct_conversion_value',
                    'purchase.stock_destination',
                ])
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $oldFactor = max(0.0001, (float) ($item->unit_conversion_value ?: 1));
                $newFactor = max(0.0001, (float) ($item->correct_conversion_value ?: 1));
                $factorRatio = $newFactor / $oldFactor;
                $newQtyBase = (int) round((float) $item->quantity * $newFactor);

                $inventoryItems = DB::table('inventory_items_2')
                    ->where('purchase_item_id', $item->id)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->get();

                $correctedPurchaseStockIn = 0;

                foreach ($inventoryItems as $inventoryItem) {
                    $oldQtyBase = (int) ($inventoryItem->qty_base ?? $inventoryItem->quantity ?? 0);
                    $oldStockIn = (int) ($inventoryItem->stock_in ?? 0);
                    $newStockIn = (int) round($oldStockIn * $factorRatio);
                    $newStockIn = min($newQtyBase, $newStockIn);

                    DB::table('inventory_stock_in_histories_2')
                        ->where('inventory_item_id', $inventoryItem->id)
                        ->whereNull('deleted_at')
                        ->update([
                            'stock_in' => DB::raw('ROUND(stock_in * '.(float) $factorRatio.')'),
                            'updated_at' => now(),
                        ]);

                    DB::table('inventory_items_2')
                        ->where('id', $inventoryItem->id)
                        ->update([
                            'unit_conversion_value' => $newFactor,
                            'qty_base' => $newQtyBase,
                            'stock_in' => $newStockIn,
                            // In this project this column stores the total receivable
                            // quantity; remaining is derived as this value - stock_in.
                            'remaining_stock_in' => $newQtyBase,
                            'updated_at' => now(),
                        ]);

                    $receivedDelta = $newStockIn - $oldStockIn;
                    $incomingDelta = ($newQtyBase - $newStockIn) - ($oldQtyBase - $oldStockIn);

                    if ($item->stock_destination === 'production') {
                        DB::table('production_stocks')
                            ->where('product_id', $item->product_id)
                            ->where('production_warehouse_id', $inventoryItem->production_warehouse_id ?? 2)
                            ->update([
                                'available_quantity' => DB::raw('available_quantity + '.(int) $receivedDelta),
                                'incoming_stock' => DB::raw('incoming_stock + '.(int) $incomingDelta),
                                'updated_at' => now(),
                            ]);
                    }

                    if ($item->stock_destination === 'warehouse') {
                        DB::table('inventory_stocks')
                            ->where('product_id', $item->product_id)
                            ->where('inventory_warehouse_id', $inventoryItem->inventory_warehouse_id ?? 1)
                            ->update([
                                'inventory_stock' => DB::raw('inventory_stock + '.(int) $receivedDelta),
                                'stock_after_sales' => DB::raw('stock_after_sales + '.(int) $receivedDelta),
                                'incoming_stock' => DB::raw('incoming_stock + '.(int) $incomingDelta),
                                'updated_at' => now(),
                            ]);
                    }

                    $correctedPurchaseStockIn += $newStockIn;
                }

                $newPurchaseStockIn = $inventoryItems->isNotEmpty()
                    ? $correctedPurchaseStockIn
                    : (int) round((float) ($item->stock_in ?? 0) * $factorRatio);

                DB::table('purchase_items')
                    ->where('id', $item->id)
                    ->update([
                        'unit_conversion_value' => $newFactor,
                        'qty_base' => $newQtyBase,
                        'stock_in' => min($newQtyBase, $newPurchaseStockIn),
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Data correction is intentionally irreversible. Restoring the invalid
        // multiplier would corrupt stock quantities again.
    }
};
