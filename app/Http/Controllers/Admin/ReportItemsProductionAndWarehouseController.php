<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportItemsProductionAndWarehouseController extends Controller
{
    public function getCombinedReportItems()
    {
        return view('erp.pages.report-items.report-items');
    }

    public function dataCombinedReportItems(Request $request)
    {
        // 🔹 Ambil semua produk aktif beserta relasinya
        $products = Products::whereNull('deleted_at')
            ->with(['productionStocks', 'inventoryStock']) // pastikan relasi singular ya
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            })
            ->orderBy('name')
            ->get();

        $data = $products->map(function ($product) {
            $prod = $product->productionStocks;
            $inv  = $product->inventoryStock;

            return [
                'name' => $product->name,
                'production_available' =>
                '<span class="text-dark">' . number_format($prod->available_quantity ?? 0, 0, ',', '.') . '</span>',
                'finished_product_stock' =>
                '<span class="text-dark">' . number_format($prod->finished_product_stock ?? 0, 0, ',', '.') . '</span>',
                'order_progress_remaining' =>
                '<span class="text-dark">' . number_format($prod->remaining_quantity ?? 0, 0, ',', '.') . '</span>',
                'inventory_stock' =>
                number_format($inv->inventory_stock ?? 0, 0, ',', '.'),
                'stock_after_sales' =>
                number_format($inv->stock_after_sales ?? 0, 0, ',', '.'),
                'incoming_stock' =>
                number_format($inv->incoming_stock ?? 0, 0, ',', '.'),
                'avg_cost' =>
                '<span class="text-primary">' . number_format($product->avg_cost ?? 0, 2, ',', '.') . '</span>',
                'fixed_cost' =>
                '<span class="text-dark">' . number_format($product->fixed_cost ?? 0, 2, ',', '.') . '</span>',
            ];
        });

        return DataTables::of($data)
            ->addIndexColumn()
            ->rawColumns([
                'production_available',
                'finished_product_stock',
                'order_progress_remaining',
                'avg_cost',
                'fixed_cost',
            ])
            ->make(true);
    }
}
