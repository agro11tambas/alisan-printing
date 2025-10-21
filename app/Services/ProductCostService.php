<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use App\Models\Products;
use Illuminate\Support\Facades\Log;

class ProductCostService
{
    // public static function updateCostAndStock(Products $product): void
    // {
    //     $productId = $product->id;

    //     // Ambil total pembelian
    //     $accountItems = PurchaseItem::where('product_id', $productId)
    //         ->where('status', 'Purchase Account')
    //         ->get();

    //     $returnItems = PurchaseReturnItem::where('product_id', $productId)
    //         ->where('status', 'Purchase Return')
    //         ->get();

    //     $accountQty   = $accountItems->sum('stock_in');
    //     $accountTotal = $accountItems->sum(fn($item) => ($item->price + $item->freight) * $item->stock_in);

    //     $returnQty   = $returnItems->sum('stock_out');
    //     $returnTotal = $returnItems->sum(fn($item) => ($item->price + $item->freight) * $item->stock_out);

    //     $netQty   = $accountQty - $returnQty;
    //     $netValue = $accountTotal - $returnTotal;

    //     // Ambil data inventory item
    //     $inventoryItems = InventoryItem::where('product_id', $productId)->get();

    //     $stockInQty  = $inventoryItems->sum('stock_in');
    //     $stockOutQty = $inventoryItems->sum('stock_out');

    //     // Ambil data inventory_stocks
    //     $inventoryStock = InventoryStock::firstOrCreate(
    //         ['product_id' => $productId],
    //         [
    //             'opening_stock'     => 0,
    //             'opening_rate'      => 0,
    //             'inventory_stock'   => 0,
    //             'stock_after_sales' => 0,
    //             'incoming_stock'    => 0,
    //             'avg_cost'          => 0,
    //         ]
    //     );

    //     $openingStock = $inventoryStock->opening_stock ?? 0;
    //     $openingRate  = $inventoryStock->opening_rate ?? 0;

    //     // Hitungan stok & cost
    //     $totalStock   = $openingStock + $stockInQty - $stockOutQty;
    //     $totalPurchase = $netQty + $openingStock;
    //     $totalValue   = $netValue + ($openingStock * $openingRate);
    //     $avgCost = $totalPurchase > 0 ? round($totalValue / $totalPurchase, 2) : 0;

    //     // Hitung total incoming stock hanya dari purchase yang sudah diakui (Purchase List / Purchase Account)
    //     $totalPurchasedQty = PurchaseItem::where('product_id', $productId)
    //         ->whereHas('purchase', function ($q) {
    //             $q->whereIn('status', ['Purchase List', 'Purchase Account']); // hanya pembelian yang valid
    //         })
    //         ->sum('quantity');

    //     $totalStockInQty = InventoryItem::where('product_id', $productId)->sum('stock_in');
    //     $incoming = max(0, $totalPurchasedQty - $totalStockInQty);


    //     // ✅ Update inventory_stock & incoming_stock
    //     $inventoryStock->update([
    //         'inventory_stock' => $totalStock,
    //         'incoming_stock'  => $incoming,
    //         'avg_cost'        => $avgCost,
    //     ]);

    //     // ❌ Jangan ubah stock_after_sales kecuali saat awal (belum ada stok sama sekali)
    //     // Jika dipanggil dari Opening Stock page, update juga stock_after_sales
    //     if (request()->is('erp/opening-stock-rate*')) {
    //         $inventoryStock->update(['stock_after_sales' => $totalStock]);
    //     }
    //     // Jika bukan dari opening, biarkan logika lama
    //     elseif ($inventoryStock->stock_after_sales == 0 && $totalStock > 0) {
    //         $inventoryStock->update(['stock_after_sales' => $totalStock]);
    //     }

    //     // 🔥 update juga ke tabel products
    //     $product->update([
    //         'avg_cost' => $avgCost,
    //     ]);
    // }

    public static function updateCostAndStock(Products $product): void
    {
        $productId = $product->id;

        // === BAGIAN LAMA (AMBIL DATA PURCHASE, RETURN, INVENTORY) ===
        $accountItems = PurchaseItem::where('product_id', $productId)
            ->where('status', 'Purchase Account')
            ->get();

        $returnItems = PurchaseReturnItem::where('product_id', $productId)
            ->where('status', 'Purchase Return')
            ->get();

        $accountQty   = $accountItems->sum('stock_in');
        $accountTotal = $accountItems->sum(fn($item) => ($item->price + $item->freight) * $item->stock_in);

        $returnQty   = $returnItems->sum('stock_out');
        $returnTotal = $returnItems->sum(fn($item) => ($item->price + $item->freight) * $item->stock_out);

        $netQty   = $accountQty - $returnQty;
        $netValue = $accountTotal - $returnTotal;

        $inventoryItems = InventoryItem::where('product_id', $productId)->get();
        $stockInQty  = $inventoryItems->sum('stock_in');
        $stockOutQty = $inventoryItems->sum('stock_out');

        $inventoryStock = InventoryStock::firstOrCreate(
            ['product_id' => $productId],
            [
                'opening_stock'     => 0,
                'opening_rate'      => 0,
                'inventory_stock'   => 0,
                'stock_after_sales' => 0,
                'incoming_stock'    => 0,
                'avg_cost'          => 0,
            ]
        );

        $openingStock = $inventoryStock->opening_stock ?? 0;
        $openingRate  = $inventoryStock->opening_rate ?? 0;

        // === BAGIAN BARU: SELIPKAN STOK PRODUCTION KE STOK LAMA (STEP 4) ===
        $productionQty = \App\Models\ProductionStock::where('product_id', $productId)
            ->sum('available_quantity'); // stok produksi yang masih ada

        // Stok lama = inventory + production
        $companyOldQty   = ($openingStock + $stockInQty - $stockOutQty) + $productionQty;
        $companyOldValue = ($openingStock * $openingRate) + $netValue + ($productionQty * $inventoryStock->avg_cost);

        // Hitung pembelian baru (net purchase)
        $purchaseQty = $netQty;        // pembelian terakhir
        $purchaseValue = $netValue;    // total nilai pembelian terakhir

        // Hitung total gabungan (stok lama + stok purchase)
        $totalQty   = $companyOldQty + $purchaseQty;
        $totalValue = $companyOldValue + $purchaseValue;

        // AVG COST BARU (gabungan inventory + production + pembelian baru)
        $avgCost = $totalQty > 0 ? round($totalValue / $totalQty, 2) : 0;

        // Update inventory_stock
        $inventoryStock->update([
            'inventory_stock' => $openingStock + $stockInQty - $stockOutQty + $purchaseQty,
            // 'incoming_stock'  => 0,
            'avg_cost'        => $avgCost,
        ]);

        // Hanya ubah stock_after_sales jika kosong
        if (request()->is('erp/opening-stock-rate*')) {
            $inventoryStock->update(['stock_after_sales' => $inventoryStock->inventory_stock]);
        } elseif ($inventoryStock->stock_after_sales == 0 && $inventoryStock->inventory_stock > 0) {
            $inventoryStock->update(['stock_after_sales' => $inventoryStock->inventory_stock]);
        }

        // Update product avg_cost
        $product->update([
            'avg_cost' => $avgCost,
        ]);
    }

    public static function restoreStockAfterSales(Products $product, int $qty): void
    {
        $inventoryStock = InventoryStock::firstOrCreate(
            ['product_id' => $product->id],
            [
                'opening_stock'     => 0,
                'opening_rate'      => 0,
                'inventory_stock'   => 0,
                'stock_after_sales' => 0,
                'incoming_stock'    => 0,
                'avg_cost'          => 0,
            ]
        );

        $inventoryStock->increment('stock_after_sales', $qty);
    }
}
