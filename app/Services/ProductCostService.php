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

        // === STOK: dihitung seperti sebelumnya ===
        $accountItems = PurchaseItem::where('product_id', $productId)
            ->where('status', 'Purchase Account')
            ->get();

        $returnItems = PurchaseReturnItem::where('product_id', $productId)
            ->where('status', 'Purchase Return')
            ->get();

        $netQty = $accountItems->sum('stock_in') - $returnItems->sum('stock_out');

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

        $inventoryStock->update([
            'inventory_stock' => $openingStock + $stockInQty - $stockOutQty + $netQty,
        ]);

        // Hanya ubah stock_after_sales jika kosong
        if (request()->is('erp/opening-stock-rate*')) {
            $inventoryStock->update(['stock_after_sales' => $inventoryStock->inventory_stock]);
        } elseif ($inventoryStock->stock_after_sales == 0 && $inventoryStock->inventory_stock > 0) {
            $inventoryStock->update(['stock_after_sales' => $inventoryStock->inventory_stock]);
        }

        // === HARGA MODAL: bukan lagi rata-rata bergerak, tapi FIFO ===
        //
        // Rata-rata bergerak seumur hidup produk membuat satu pembelian mahal
        // mencemari harga modal seluruh penjualan lama. FIFO memakan batch dari
        // yang paling tua, jadi tiap penjualan dinilai dengan harga barang yang
        // benar-benar dia ambil.
        //
        // products.avg_cost dan inventory_stocks.avg_cost dipertahankan nama
        // kolomnya supaya layar dan laporan lama tetap jalan, tapi isinya kini
        // harga rata-rata tertimbang dari SISA batch menurut FIFO — itulah nilai
        // persediaan yang benar. Yang mengisinya adalah FifoCostService.
        app(FifoCostService::class)->rebuild([$productId]);
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
