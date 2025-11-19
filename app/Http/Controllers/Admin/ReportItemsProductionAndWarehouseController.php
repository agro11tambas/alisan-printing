<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->with(['productionStocks', 'inventoryStock'])
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $search = $request->product_name;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name', 'asc') // urut di query juga (optional)
            ->get();

        $data = $products->map(function ($product) {
            $prod = $product->productionStocks;
            $inv  = $product->inventoryStock;

            // ambil nilai asli
            $avg = $product->avg_cost ?? 0;
            $fixed = $product->fixed_cost ?? 0;

            // fungsi pembulatan kustom ke 2 angka di belakang koma
            $customRound = function ($value) {
                $temp = $value * 1000; // ubah ke 3 digit belakang koma
                $lastDigit = $temp % 10; // ambil angka ketiga di belakang koma
                if ($lastDigit <= 5) {
                    // bulat ke bawah
                    return floor($value * 100) / 100;
                } else {
                    // bulat ke atas
                    return ceil($value * 100) / 100;
                }
            };

            $avgRounded = $customRound($avg);
            $fixedRounded = $customRound($fixed);

            return [
                'name' => $product->name,
                'production_available' =>
                '<span class="text-dark">' . number_format($prod->available_quantity ?? 0, 0, ',', '.') . '</span>',
                // 'finished_product_stock' =>
                // '<span class="text-dark">' . number_format($prod->finished_product_stock ?? 0, 0, ',', '.') . '</span>',
                'finished_product_stock' => (function () use ($product) {

                    $productId = $product->id;

                    // 1️⃣ Total qty selesai produksi
                    $totalCompleted = \App\Models\OrderProgressItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('completed_quantity');

                    // 2️⃣ Total qty sudah dikirim
                    $totalShipped = \App\Models\DeliveryOrderItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('shipped_qty');

                    $finished = $totalCompleted - $totalShipped;
                    if ($finished < 0) $finished = 0;

                    return '<span class="text-dark">' . number_format($finished, 0, ',', '.') . '</span>';
                })(),
                'order_progress_remaining' =>
                '<span class="text-dark">' . number_format($prod->remaining_quantity ?? 0, 0, ',', '.') . '</span>',
                'inventory_stock' =>
                number_format($inv->inventory_stock ?? 0, 0, ',', '.'),
                'stock_after_sales' =>
                number_format($inv->stock_after_sales ?? 0, 0, ',', '.'),
                // hitung incoming stock aktual dari inventory_items_2
                'incoming_stock' => (function () use ($product) {
                    $incoming = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                        ->value('incoming');

                    return number_format($incoming ?? 0, 0, ',', '.');
                })(),
                'incoming_stock_production' =>
                number_format($prod->incoming_stock ?? 0, 0, ',', '.'),
                'avg_cost' =>
                '<span class="text-primary">' . number_format($avgRounded, 2, ',', '.') . '</span>',
                'fixed_cost' =>
                '<span class="text-dark">' . number_format($fixedRounded, 2, ',', '.') . '</span>',
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
                'incoming_stock_production',
            ])
            ->make(true);
    }
}
