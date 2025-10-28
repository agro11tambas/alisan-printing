<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionStock;
use App\Models\ProductionWarehouse;
use App\Models\OpeningStockProduction;
use App\Models\Products;

class OpeningStockProductionController extends Controller
{
    public function getOpeningStockProduction()
    {
        $openingStockProductions = ProductionStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->get();

        return view('erp.pages.production.opening-stock.opening-stock', compact('openingStockProductions'));
    }

    public function edit()
    {
        $openingStockProductions = ProductionStock::with('product')
            ->whereHas('product')
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->get();

        foreach ($openingStockProductions as $productionStock) {
            $productionStock->opening_stock = $productionStock->opening_stock;
            $productionStock->finished_product_stock = $productionStock->finished_product_stock;
        }
        return view('erp.pages.production.opening-stock.edit-opening-stock', compact('openingStockProductions'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'                              => 'required|array',
            'opening_stock'                   => 'required|array',
            'opening_finished_product_stock'  => 'required|array',
        ]);

        foreach ($request->id as $index => $id) {
            $productionStock = ProductionStock::find($id);
            if (!$productionStock) continue;

            $newOpeningStock   = (int) $request->opening_stock[$index];
            $newOpeningFinished = (int) $request->opening_finished_product_stock[$index];

            // Ambil nilai lama
            $oldOpeningStock    = (int) $productionStock->opening_stock;
            $oldOpeningFinished = (int) $productionStock->opening_finished_product_stock;
            $oldAvailable       = (int) $productionStock->available_quantity;
            $oldFinishedStock   = (int) $productionStock->finished_product_stock;

            // Hitung selisih
            $diffOpening        = $newOpeningStock - $oldOpeningStock;
            $diffOpeningFinished = $newOpeningFinished - $oldOpeningFinished;

            // Update stok
            $productionStock->update([
                'opening_stock'                  => $newOpeningStock,
                'opening_finished_product_stock' => $newOpeningFinished,
                'available_quantity'             => $oldAvailable + $diffOpening,
                'finished_product_stock'         => $oldFinishedStock + $diffOpeningFinished,
            ]);

            // 🔹 Sinkronisasi ke InventoryStock (increment stock_after_sales)
            $inventoryStock = \App\Models\InventoryStock::where('product_id', $productionStock->product_id)->first();

            if ($inventoryStock) {
                $inventoryStock->update([
                    'stock_after_sales' => $inventoryStock->stock_after_sales + $diffOpening,
                ]);
            }
        }

        return redirect('/erp/productions/opening-stock')
            ->with('success', 'Opening Stock Production updated successfully.');
    }
}
