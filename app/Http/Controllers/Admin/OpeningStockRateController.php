<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use Illuminate\Http\Request;
use App\Models\Products;
use App\Services\ProductCostService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OpeningStockRateController extends Controller
{
    public function index()
    {
        return view('erp.pages.opening-stock-rate.index');
    }

    public function dataOpeningStockOverview(Request $request)
    {
        $products = Products::with(['inventoryStock', 'productionStocks'])
            ->orderBy('name');

        return DataTables::of($products)
            ->addIndexColumn()
            ->addColumn('product_name', fn($row) => $row->name)
            ->addColumn('opening_stock', fn($row) => number_format($row->inventoryStock->opening_stock ?? 0, 0, ',', '.'))
            ->addColumn('opening_rate', fn($row) => number_format($row->inventoryStock->opening_rate ?? 0, 2, ',', '.'))
            ->addColumn('minimum_stock', fn($row) => number_format($row->inventoryStock->minimum_stock ?? 0, 0, ',', '.'))
            ->addColumn('production_stock', fn($row) => number_format($row->productionStocks->opening_stock ?? 0, 0, ',', '.'))
            ->rawColumns(['product_name'])
            ->make(true);
    }

    public function edit()
    {
        $openingStockRates = InventoryStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'inventory_stocks.product_id')
            )
            ->get();

        $openingStockProductions = ProductionStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->get();

        return view('erp.pages.opening-stock-rate.edit-opening-stock-overview', compact(
            'openingStockRates',
            'openingStockProductions'
        ));
    }

    // public function update(Request $request)
    // {
    //     $request->validate([
    //         // Inventory validation
    //         'inv_id'           => 'required|array',
    //         'opening_stock'    => 'required|array',
    //         'opening_rate'     => 'required|array',
    //         'minimum_stock'    => 'required|array',

    //         // Production validation (nullable biar gak error)
    //         'prod_id'                  => 'nullable|array',
    //         'opening_stock_production' => 'nullable|array',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // 🔹 Update INVENTORY STOCK
    //         foreach ($request->inv_id as $index => $id) {
    //             $stock = InventoryStock::find($id);
    //             if (!$stock) continue;

    //             $oldOpeningStock = (float) $stock->opening_stock;
    //             $newOpeningStock = (float) ($request->opening_stock[$index] ?? 0);
    //             $newOpeningRate  = (float) ($request->opening_rate[$index] ?? 0);
    //             $newMinimumStock = (float) ($request->minimum_stock[$index] ?? 0);

    //             $diff = $newOpeningStock - $oldOpeningStock;

    //             $stock->update([
    //                 'opening_stock'     => $newOpeningStock,
    //                 'opening_rate'      => $newOpeningRate,
    //                 'minimum_stock'     => $newMinimumStock,
    //                 'inventory_stock'   => max(0, $stock->inventory_stock + $diff),
    //                 'stock_after_sales' => max(0, $stock->stock_after_sales + $diff),
    //             ]);

    //             // 🔹 Update average cost hanya jika opening_rate berubah
    //             if ((float) $stock->opening_rate !== (float) $newOpeningRate) {
    //                 // Recalculate avg_cost secara proporsional
    //                 $totalQty  = max(1, $stock->inventory_stock); // hindari div 0
    //                 $newAvgCost = round((($stock->avg_cost * $oldOpeningStock) + ($newOpeningRate * $diff)) / $totalQty, 2);

    //                 $stock->update(['avg_cost' => $newAvgCost]);
    //             }
    //         }

    //         // 🔹 Update PRODUCTION STOCK
    //         if ($request->filled('prod_id')) {
    //             foreach ($request->prod_id as $index => $id) {
    //                 if (!$id) continue; // skip kalau kosong (karena mungkin belum punya record)
    //                 $productionStock = ProductionStock::find($id);
    //                 if (!$productionStock) continue;

    //                 $newOpeningStock = (int) ($request->opening_stock_production[$index] ?? 0);
    //                 $oldOpeningStock = (int) $productionStock->opening_stock;
    //                 $oldAvailable    = (int) $productionStock->available_quantity;

    //                 $diffOpening = $newOpeningStock - $oldOpeningStock;

    //                 $productionStock->update([
    //                     'opening_stock'      => $newOpeningStock,
    //                     'available_quantity' => $oldAvailable + $diffOpening,
    //                 ]);

    //                 // 🔹 Sinkron ke inventory (update stok akhir)
    //                 $inventoryStock = InventoryStock::where('product_id', $productionStock->product_id)->first();
    //                 if ($inventoryStock) {
    //                     $inventoryStock->update([
    //                         'stock_after_sales' => $inventoryStock->stock_after_sales + $diffOpening,
    //                     ]);
    //                 }
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/opening-stock')
    //             ->with('success', 'Opening Stock Overview updated successfully.');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Failed to update Opening Stock: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request)
    {
        $request->validate([
            // Inventory validation
            'inv_id'           => 'required|array',
            'opening_stock'    => 'required|array',
            'opening_rate'     => 'required|array',
            'minimum_stock'    => 'required|array',

            // Production validation
            'prod_id'                  => 'nullable|array',
            'opening_stock_production' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // 🔹 Update INVENTORY STOCK
            foreach ($request->inv_id as $index => $id) {
                $stock = InventoryStock::find($id);
                if (!$stock) continue;

                $oldOpeningStock = (float) $stock->opening_stock;
                $oldOpeningRate  = (float) $stock->opening_rate;
                $oldAvgCost      = (float) $stock->avg_cost;
                $oldInventoryStock = (float) $stock->inventory_stock;
                $oldTotalStock   = $oldInventoryStock; // simpan stok lama sebelum update

                $newOpeningStock = (float) ($request->opening_stock[$index] ?? 0);
                $newOpeningRate  = (float) ($request->opening_rate[$index] ?? 0);
                $newMinimumStock = (float) ($request->minimum_stock[$index] ?? 0);

                $diff = $newOpeningStock - $oldOpeningStock;

                // 🔹 Update stok dasar
                $stock->update([
                    'opening_stock'     => $newOpeningStock,
                    'opening_rate'      => $newOpeningRate,
                    'minimum_stock'     => $newMinimumStock,
                    'inventory_stock'   => max(0, $stock->inventory_stock + $diff),
                    'stock_after_sales' => max(0, $stock->stock_after_sales + $diff),
                ]);

                // 🔹 Hanya ubah avg_cost kalau rate-nya berubah
                if ($oldOpeningRate !== $newOpeningRate || $oldAvgCost == 0) {
                    $newTotalStock = max(1, $newOpeningStock); // gunakan stok baru, bukan stok lama

                    $newAvgCost = round(
                        (
                            ($oldAvgCost * $oldTotalStock)
                            - ($oldOpeningStock * $oldOpeningRate)
                            + ($newOpeningStock * $newOpeningRate)
                        ) / $newTotalStock,
                        2
                    );

                    $stock->update(['avg_cost' => $newAvgCost]);

                    if ($stock->product) {
                        $stock->product->update(['avg_cost' => $newAvgCost]);
                    }
                }
            }

            // 🔹 Update PRODUCTION STOCK
            if ($request->filled('prod_id')) {
                foreach ($request->prod_id as $index => $id) {
                    if (!$id) continue;
                    $productionStock = ProductionStock::find($id);
                    if (!$productionStock) continue;

                    $newOpeningStock = (int) ($request->opening_stock_production[$index] ?? 0);
                    $oldOpeningStock = (int) $productionStock->opening_stock;
                    $oldAvailable    = (int) $productionStock->available_quantity;

                    $diffOpening = $newOpeningStock - $oldOpeningStock;

                    $productionStock->update([
                        'opening_stock'      => $newOpeningStock,
                        'available_quantity' => $oldAvailable + $diffOpening,
                    ]);

                    // 🔹 Sinkron ke inventory (update stok akhir)
                    $inventoryStock = InventoryStock::where('product_id', $productionStock->product_id)->first();
                    if ($inventoryStock) {
                        $inventoryStock->update([
                            'stock_after_sales' => $inventoryStock->stock_after_sales + $diffOpening,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect('/erp/opening-stock')
                ->with('success', 'Opening Stock Overview updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Opening Stock: ' . $e->getMessage());
        }
    }
}
