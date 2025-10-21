<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OpeningStockRateController extends Controller
{
    public function getOpeningStockRate()
    {
        $openingStockRates = InventoryStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'inventory_stocks.product_id')
            )
            ->get();

        return view('erp.pages.opening-stock-rate.opening-stock-rate', compact('openingStockRates'));
    }

    public function dataOpeningStockRate()
    {
        $openingStockRate = InventoryStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'inventory_stocks.product_id')
            )
            ->get();

        return DataTables::of($openingStockRate)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->product ? $row->product->name : '-';
            })
            ->addColumn('inventory_stock', function ($row) {
                return $row->inventory_stock;
            })
            ->addColumn('minimum_stock', function ($row) {
                return $row->minimum_stock;
            })
            ->addColumn('avg_cost', function ($row) {
                return $row->avg_cost;
            })
            ->addColumn('action', function ($row) {
                return view('erp.pages.opening-stock-rate.partials.action-button', compact('row'));
            })
            ->rawColumns(['action'])
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

        foreach ($openingStockRates as $product) {
            $product->opening_stock = $product->opening_stock;
            $product->opening_rate = $product->opening_rate;
        }
        return view('erp.pages.opening-stock-rate.edit-opening-stock-rate', compact('openingStockRates'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|array',
            'opening_stock'  => 'required|array',
            'opening_rate'   => 'required|array',
            'minimum_stock'  => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->id as $index => $id) {
                $stock = InventoryStock::find($id);
                if (!$stock) continue;

                $oldOpeningStock = (float) $stock->opening_stock;
                $newOpeningStock = (float) $request->opening_stock[$index];
                $newOpeningRate  = (float) $request->opening_rate[$index];
                $newMinimumStock = (float) $request->minimum_stock[$index];

                // 🔹 Hitung selisih opening stock
                $diff = $newOpeningStock - $oldOpeningStock;

                // 🔹 Update opening_stock + stok terkait
                $stock->update([
                    'opening_stock'     => $newOpeningStock,
                    'opening_rate'      => $newOpeningRate,
                    'minimum_stock'     => $newMinimumStock,
                    'inventory_stock'   => max(0, $stock->inventory_stock + $diff),
                    'stock_after_sales' => max(0, $stock->stock_after_sales + $diff),
                ]);

                // 🔹 Update total stok di tabel produk
                // $product = $stock->product;
                // if ($product) {
                //     $totalInventory  = InventoryStock::where('product_id', $product->id)->sum('inventory_stock');
                //     $totalAfterSales = InventoryStock::where('product_id', $product->id)->sum('stock_after_sales');

                //     $product->update([
                //         'inventory_stock'   => $totalInventory,
                //         'stock_after_sales' => $totalAfterSales,
                //     ]);
                // }

                // 🔹 Update cost (optional)
                \App\Services\ProductCostService::updateCostAndStock($stock->product);
            }

            DB::commit();
            return redirect('/erp/opening-stock-rate')
                ->with('success', 'Opening Stock Rate updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Opening Stock Rate: ' . $e->getMessage());
        }
    }
}
