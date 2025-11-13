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

    public function dataReportItems(Request $request)
    {
        // 🔹 Query utama, hanya ambil yang punya product aktif (tidak soft-deleted)
        $reportItems = ProductionStock::whereHas('product', function ($q) {
            $q->whereNull('products.deleted_at');
        })
            ->with('product');

        if ($request->filled('product_name')) {
            $keyword = $request->product_name;

            $reportItems->whereHas('product', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('sku', 'like', '%' . $keyword . '%');
                });
            });
        }

        // 🔤 Urutkan nama produk setelah stok (opsional, kayak di Inventory)
        $reportItems->orderBy(
            Products::select('name')
                ->whereColumn('products.id', 'production_stocks.product_id')
        );

        // ✅ Eksekusi query
        $reportItems = $reportItems->get();

        // 🔹 DataTables response
        return DataTables::of($reportItems)
            ->addIndexColumn()
            ->addColumn('name', fn($item) => e($item->product->name ?? '-'))
            ->addColumn(
                'available_quantity',
                fn($item) => number_format($item->available_quantity ?? 0, 0, ',', '.')
            )
            // ->addColumn(
            //     'finished_product_stock',
            //     fn($item) => number_format($item->finished_product_stock ?? 0, 0, ',', '.')
            // )
            ->addColumn('finished_product_stock', function ($item) {

                $productId = $item->product_id;

                // 1️⃣ Total qty sudah selesai produksi
                $totalCompleted = \App\Models\OrderProgressItem::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('completed_quantity');

                // 2️⃣ Total qty sudah dikirim
                $totalShipped = \App\Models\DeliveryOrderItem::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('shipped_qty');

                $finished = $totalCompleted - $totalShipped;
                if ($finished < 0) $finished = 0;

                return number_format($finished, 0, ',', '.');
            })
            ->addColumn(
                'incoming_stock',
                fn($item) => number_format($item->incoming_stock ?? 0, 0, ',', '.')
            )
            // ->addColumn(
            //     'pending_waiting_list',
            //     fn($item) => number_format($item->pending_waiting_list ?? 0, 0, ',', '.')
            // )
            ->addColumn('pending_waiting_list', function ($item) {

                $productId = $item->product_id;

                // 1️⃣ Total QTY dari Design Items
                $totalDesignQty = \App\Models\DesignItem::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('quantity');

                // 2️⃣ Total Assigned QTY
                $totalAssignedQty = \App\Models\OrderProgressAssign::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('assigned_quantity');

                $pending = $totalDesignQty - $totalAssignedQty;

                if ($pending < 0) $pending = 0;

                return number_format($pending, 0, ',', '.');
            })
            ->addColumn('on_delivery', function ($item) {

                $productId = $item->product_id;

                // 1️⃣ Total shipped_qty dari delivery_order_items
                $totalOrderShipped = \App\Models\DeliveryOrderItem::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('shipped_qty');

                // 2️⃣ Total shipped_quantity dari delivery_list_items, 
                //    hanya jika parent-nya (delivery_lists) status = finished
                $totalListShipped = \App\Models\DeliveryListItem::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereHas('shipment', function ($q) {
                        $q->where('status', 'finished');  // 🔥 yang benar disini
                    })
                    ->sum('shipped_quantity');

                $onDelivery = $totalOrderShipped - $totalListShipped;
                if ($onDelivery < 0) $onDelivery = 0;

                return number_format($onDelivery, 0, ',', '.');
            })
            ->addColumn('action', fn($item) => '
            <button type="button" class="btn btn-sm btn-outline-danger btnDefect" 
                data-product-id="' . $item->product_id . '" 
                data-name="' . e($item->product->name ?? '-') . '">
                <i class="feather-alert-triangle me-1"></i> Defect
            </button>
        ')
            ->rawColumns(['action'])
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
