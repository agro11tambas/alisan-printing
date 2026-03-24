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
        $today = today()->toDateString();
        // 🔹 Query utama, hanya ambil yang punya product aktif (tidak soft-deleted)
        $reportItems = ProductionStock::query()
            ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
            ->with(['product', 'todaySnapshot'])
            ->select(
                'production_stocks.*',
                DB::raw('
            GREATEST(
                COALESCE((
                    SELECT SUM(quantity)
                    FROM design_items
                    WHERE design_items.product_id = production_stocks.product_id
                    AND design_items.deleted_at IS NULL
                ),0)
                -
                COALESCE((
                    SELECT SUM(assigned_quantity)
                    FROM order_progress_assigns
                    WHERE order_progress_assigns.product_id = production_stocks.product_id
                    AND order_progress_assigns.deleted_at IS NULL
                ),0),
                0
            ) AS pending_waiting_list
        ')
            )
            ->orderByDesc('pending_waiting_list');


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
            // ->addColumn('incoming_stock', function ($item) {

            //     $incoming = DB::table('material_request_items')
            //         ->where('product_id', $item->product_id)
            //         ->whereNull('deleted_at')
            //         ->selectRaw('SUM(requested_qty - received_qty) AS incoming')
            //         ->value('incoming');

            //     return number_format($incoming ?? 0, 0, ',', '.');
            // })

            ->addColumn('incoming_stock', function ($item) {

                $productId = $item->product_id;

                // 1️⃣ Incoming dari Material Request
                $incomingMaterial = DB::table('material_request_items')
                    ->where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->selectRaw('SUM(requested_qty - received_qty) AS incoming')
                    ->value('incoming');

                // 2️⃣ Incoming dari Inventory Items (Production Warehouse)
                $incomingInventory = DB::table('inventory_items_2')
                    ->where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereNotNull('purchase_item_id')
                    ->whereNotNull('production_warehouse_id') // 🔥 GANTI DI SINI
                    ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                    ->value('incoming');

                $incomingTotal = ($incomingMaterial ?? 0) + ($incomingInventory ?? 0);

                return number_format($incomingTotal, 0, ',', '.');
            })

            ->addColumn(
                'pending_waiting_list',
                fn($item) => number_format($item->pending_waiting_list ?? 0, 0, ',', '.')
            )

            ->addColumn('assigned_minus_completed', function ($item) {

                $productId = $item->product_id;

                // 1️⃣ Total Assigned
                $totalAssigned = \App\Models\OrderProgressAssign::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->sum('assigned_quantity');

                // 2️⃣ Total Completed
                $totalCompleted = \App\Models\OrderProgressAssign::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->selectRaw('SUM(completed_quantity + defect_quantity + reject_quantity) as total')
                    ->value('total');

                $result = $totalAssigned - $totalCompleted;

                if ($result < 0) $result = 0;

                return number_format($result, 0, ',', '.');
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
            ->addColumn('opening_stock_today', function ($item) {
                // Ambil dari snapshot hari ini, fallback ke available_quantity jika belum ada snapshot
                $opening = $item->todaySnapshot?->opening_stock ?? $item->available_quantity ?? 0;
                return number_format($opening, 0, ',', '.');
            })

            ->addColumn('closing_stock_today', function ($item) {
                // Closing = available_quantity real-time saat ini
                return number_format($item->available_quantity ?? 0, 0, ',', '.');
            })

            // ->addColumn('assign_today', function ($item) use ($today) {
            //     $productId = $item->product_id;

            //     // 1️⃣ assigned_minus_completed yang created_at-nya hari ini
            //     $assignedToday = \App\Models\OrderProgressAssign::where('product_id', $productId)
            //         ->whereNull('deleted_at')
            //         ->whereDate('created_at', $today)
            //         ->sum('assigned_quantity');

            //     $completedToday = \App\Models\OrderProgressAssign::where('product_id', $productId)
            //         ->whereNull('deleted_at')
            //         ->whereDate('created_at', $today)
            //         ->selectRaw('SUM(completed_quantity + defect_quantity + reject_quantity) as total')
            //         ->value('total');

            //     $assignMinusCompletedToday = max($assignedToday - ($completedToday ?? 0), 0);

            //     // 2️⃣ finished_product_stock yang created_at-nya hari ini
            //     $completedQtyToday = \App\Models\OrderProgressItem::where('product_id', $productId)
            //         ->whereNull('deleted_at')
            //         ->whereDate('created_at', $today)
            //         ->sum('completed_quantity');

            //     $shippedQtyToday = \App\Models\DeliveryOrderItem::where('product_id', $productId)
            //         ->whereNull('deleted_at')
            //         ->whereDate('created_at', $today)
            //         ->sum('shipped_qty');

            //     $finishedToday = max($completedQtyToday - $shippedQtyToday, 0);

            //     // 3️⃣ Total assign today
            //     $assignToday = $assignMinusCompletedToday + $finishedToday;

            //     return number_format($assignToday, 0, ',', '.');
            // })

            ->addColumn('stock_in_today', function ($item) use ($today) {
                $productId = $item->product_id;

                // 1️⃣ received_qty dari material_request_items hari ini
                $fromMaterial = DB::table('material_request_items')
                    ->where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $today)
                    ->sum('received_qty');

                // 2️⃣ stock_in dari inventory_items_2 hari ini
                $fromInventory = DB::table('inventory_items_2')
                    ->where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereNotNull('purchase_item_id')
                    ->whereNotNull('production_warehouse_id')
                    ->whereDate('created_at', $today)
                    ->sum('stock_in');

                $total = ($fromMaterial ?? 0) + ($fromInventory ?? 0);

                return number_format($total, 0, ',', '.');
            })

            ->addColumn('assign_today', function ($item) use ($today) {
                $productId = $item->product_id;

                $assignToday = \App\Models\OrderProgressAssign::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $today)
                    ->sum('assigned_quantity');

                return number_format($assignToday, 0, ',', '.');
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
