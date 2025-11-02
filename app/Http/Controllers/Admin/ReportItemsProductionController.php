<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductionStock;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReportItemsProductionController extends Controller
{
    public function getReportItems()
    {
        return view('erp.pages.production.report-items.report-items');
    }

    // public function dataReportItems(Request $request)
    // {
    //     $reportItems = ProductionStock::whereHas('product', function ($q) {
    //         $q->whereNull('products.deleted_at');
    //     })->with('product')->orderBy('remaining_quantity', 'desc');;

    //     if ($request->filled('product_name')) {
    //         $reportItems->whereHas('product', function ($q) use ($request) {
    //             $q->where('name', 'like', '%' . $request->product_name . '%');
    //         });
    //     }

    //     return DataTables::eloquent($reportItems)
    //         ->addIndexColumn()
    //         ->addColumn('name', function ($reportItem) {
    //             return $reportItem->product->name;
    //         })
    //         ->addColumn('available_quantity', function ($reportItem) {
    //             return '<span class="text-danger">' . number_format($reportItem->available_quantity, 0, ',', '.') . '</span>';
    //         })
    //         ->addColumn('finished_product_stock', function ($reportItem) {
    //             return '<span class="text-primary">' . number_format($reportItem->finished_product_stock, 0, ',', '.') . '</span>';
    //         })
    //         ->addColumn(
    //             'order_progress_remaining',
    //             fn($reportItem) =>
    //             '<span class="text-success">' . number_format($reportItem->remaining_quantity, 0, ',', '.') . '</span>'
    //         )
    //         ->addColumn('action', function ($row) {
    //             return '
    //                 <button type="button" 
    //                     class="btn btn-sm btn-outline-danger btnDefect" 
    //                     data-product-id="' . $row->product_id . '" 
    //                     data-name="' . e($row->product->name) . '">
    //                     <i class="feather-alert-triangle me-1"></i> Defect
    //                 </button>
    //             ';
    //         })
    //         ->rawColumns(['available_quantity', 'finished_product_stock', 'order_progress_remaining', 'action'])
    //         ->make(true);
    // }
    
    public function dataReportItems(Request $request)
    {
        // ambil semua data dari Eloquent
        $reportItems = ProductionStock::with(['product' => fn($q) => $q->whereNull('products.deleted_at')])
            ->get();

        // filter product name (kalau ada)
        if ($request->filled('product_name')) {
            $reportItems = $reportItems->filter(function ($item) use ($request) {
                return stripos($item->product->name, $request->product_name) !== false;
            });
        }

        // urutkan berdasarkan accessor remaining_quantity (dari besar ke kecil)
        $reportItems = $reportItems->sortByDesc(fn($item) => $item->remaining_quantity)->values();

        return DataTables::of($reportItems)
            ->addIndexColumn()
            ->addColumn('name', fn($item) => e($item->product->name))
            ->addColumn(
                'available_quantity',
                fn($item) =>
                '<span class="text-danger">' . number_format($item->available_quantity, 0, ',', '.') . '</span>'
            )
            ->addColumn(
                'finished_product_stock',
                fn($item) =>
                '<span class="text-primary">' . number_format($item->finished_product_stock, 0, ',', '.') . '</span>'
            )
            ->addColumn(
                'order_progress_remaining',
                fn($item) =>
                '<span class="text-success">' . number_format($item->remaining_quantity, 0, ',', '.') . '</span>'
            )
            ->addColumn('action', fn($row) => '
            <button type="button" 
                class="btn btn-sm btn-outline-danger btnDefect" 
                data-product-id="' . $row->product_id . '" 
                data-name="' . e($row->product->name) . '">
                <i class="feather-alert-triangle me-1"></i> Defect
            </button>
        ')
            ->rawColumns(['available_quantity', 'finished_product_stock', 'order_progress_remaining', 'action'])
            ->make(true);
    }


    public function storeProduction(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:1',
            'note'       => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $productId = $request->product_id;
            $quantity  = (int) $request->quantity;

            // 🔹 Cari stok production berdasarkan product_id
            $production = \App\Models\ProductionStock::where('product_id', $productId)->first();

            if (!$production) {
                throw new \Exception('Production stock record not found for this product.');
            }

            // 🔹 Cek apakah stok cukup
            if ($production->available_quantity < $quantity) {
                throw new \Exception('Insufficient production stock for defect input.');
            }

            // 🔹 Simpan data defect
            $defect = \App\Models\DefectProduct::create([
                'product_id'  => $productId,
                'quantity'    => $quantity,
                'defect_date' => now(),
                'status'      => 'pending',
                'note'        => $request->note,
                'user_id'     => Auth::id(),
                'defect_type' => 'production', // supaya tau asalnya dari produksi
            ]);

            // 🔹 Kurangi stok produksi
            $production->decrement('available_quantity', $quantity);

            DB::commit();

            return response()->json([
                'message' => 'Defect product successfully recorded and production stock updated.',
                'data'    => $defect
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to store production defect: ' . $e->getMessage()
            ], 500);
        }
    }
}
