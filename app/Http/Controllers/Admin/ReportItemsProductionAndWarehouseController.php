<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReportItemsProductionAndWarehouseController extends Controller
{
    private function applyDateFilter($query, Request $request)
    {
        switch ($request->filter) {
            case 'today':
                return $query->whereDate('created_at', Carbon::today());
            case 'last_7_days':
                return $query->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()]);
            case 'this_month':
                return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
            case 'last_30_days':
                return $query->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()]);
            case 'year_to_date':
                return $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()]);
            case 'yearly':
                return $query->whereYear('created_at', Carbon::now()->year);
            case 'custom':
                if ($request->start_date && $request->end_date) {
                    return $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
                }
                return $query;
            default:
                return $query; // all time
        }
    }

    public function getCombinedReportItems()
    {
        return view('erp.pages.report-items.report-items');
    }

    public function dataCombinedReportItems(Request $request)
    {
        // 🔹 Ambil semua produk aktif beserta relasinya
        $products = Products::whereNull('deleted_at')
            ->with(['productionStocks', 'inventoryStock', 'unitConversions'])
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $search = $request->product_name;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name', 'asc') // urut di query juga (optional)
            ->get();

        $data = $products->map(function ($product) use ($request) {
            $prod = $product->productionStocks;
            $inv  = $product->inventoryStock;

            // ambil nilai asli
            $avg = $product->avg_cost ?? 0;

            $baseUnitConversion = $product->unitConversions
                ->firstWhere('unit_id', $product->base_unit_id);

            $fixed = $baseUnitConversion?->fixed_cost ?? 0;

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
                'production_opening_stock' =>
                '<span class="text-dark">' . number_format($prod->opening_stock ?? 0, 0, ',', '.') . '</span>',
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
                'opening_stock_warehouse' =>
                number_format($inv->opening_stock ?? 0, 0, ',', '.'),
                'inventory_stock' =>
                number_format($inv->inventory_stock ?? 0, 0, ',', '.'),
                // 'stock_after_sales' =>
                // number_format($inv->stock_after_sales ?? 0, 0, ',', '.'),
                'stock_after_sales' => (function () use ($product, $inv) {

                    $productId = $product->id;

                    // Inventory stock
                    $inventoryStock = (int) ($inv->inventory_stock ?? 0);

                    // Production stock (available stock)
                    $productionAvailable = \App\Models\ProductionStock::where('product_id', $productId)
                        ->sum('available_quantity');

                    /**
                     * HITUNG PENDING WAITING LIST
                     * total design - total assigned
                     */
                    $totalDesignQty = \App\Models\DesignItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_conversion_value, 1)), 0) as total')
                        ->value('total');

                    $totalAssignedQty = \App\Models\OrderProgressAssign::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('assigned_quantity');

                    $pendingWaitingList = $totalDesignQty - $totalAssignedQty;
                    if ($pendingWaitingList < 0) $pendingWaitingList = 0;

                    /**
                     * MODE CEK
                     * Kalau ada order dengan mode = polosan → tidak mengurangi stock
                     * Kalau printing → pakai formula baru
                     */
                    $isPolosan = \App\Models\OrderItem::where('product_id', $productId)
                        ->whereHas(
                            'order',
                            fn($q) =>
                            $q->where('status', 'sale list')
                                ->where('mode', 'polosan')
                        )
                        ->exists();

                    if ($isPolosan) {
                        // POLOSAN = tidak dikurangi apa pun
                        $final = $inventoryStock + $productionAvailable + $pendingWaitingList;
                    } else {
                        // PRINTING = inventory + production - pending waiting list
                        $final = $inventoryStock + $productionAvailable - $pendingWaitingList;
                    }

                    return number_format($final, 0, ',', '.');
                })(),
                // hitung incoming stock aktual dari inventory_items_2
                'incoming_stock' => (function () use ($product, $request) {
                    // $incoming = DB::table('inventory_items_2')
                    //     ->where('product_id', $product->id)
                    //     ->whereNotNull('purchase_item_id')
                    //     ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                    //     ->value('incoming');

                    $incomingQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at')
                        ->whereNotNull(['purchase_item_id', 'inventory_warehouse_id']);

                    $incomingQuery = $this->applyDateFilter($incomingQuery, $request);

                    $incoming = $incomingQuery
                        ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                        ->value('incoming');


                    return number_format($incoming ?? 0, 0, ',', '.');
                })(),
                'incoming_stock_completed' => (function () use ($product, $request) {
                    $incomingQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNotNull(['purchase_item_id', 'inventory_warehouse_id']);

                    $incomingQuery = $this->applyDateFilter($incomingQuery, $request);

                    $incoming = $incomingQuery
                        ->selectRaw('SUM(stock_in) AS incoming')
                        ->value('incoming');


                    return number_format($incoming ?? 0, 0, ',', '.');
                })(),
                'outgoing_stock' => (function () use ($product, $request) {
                    // $outgoing = DB::table('inventory_items_2')
                    //     ->where('product_id', $product->id)
                    //     ->whereNotNull('material_request_item_id')
                    //     ->selectRaw('SUM(remaining_stock_in - stock_out) AS outgoing')
                    //     ->value('outgoing');

                    $outgoingQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNotNull('material_request_item_id')
                        ->whereNull('deleted_at');

                    $outgoingQuery = $this->applyDateFilter($outgoingQuery, $request);

                    $outgoing = $outgoingQuery
                        ->selectRaw('SUM(remaining_stock_in - stock_out - stock_in) AS outgoing')
                        ->value('outgoing');

                    return number_format($outgoing ?? 0, 0, ',', '.');
                })(),
                'outgoing_stock_completed' => (function () use ($product, $request) {
                    // $outgoing = DB::table('inventory_items_2')
                    //     ->where('product_id', $product->id)
                    //     ->whereNotNull('material_request_item_id')
                    //     ->selectRaw('SUM(stock_out) AS outgoing')
                    //     ->value('outgoing');

                    $outgoingQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNotNull('material_request_item_id')
                        ->whereNull('deleted_at');

                    $outgoingQuery = $this->applyDateFilter($outgoingQuery, $request);

                    $outgoing = $outgoingQuery
                        ->selectRaw('SUM(stock_out - stock_in) AS outgoing')
                        ->value('outgoing');

                    return number_format($outgoing ?? 0, 0, ',', '.');
                })(),
                // 'incoming_stock_production' =>
                // number_format($prod->incoming_stock ?? 0, 0, ',', '.'),
                // 'incoming_stock_production' => (function () use ($product, $request) {
                //     $incomingProdQuery = DB::table('material_request_items')
                //         ->where('product_id', $product->id)
                //         ->whereNull('deleted_at');

                //     $incomingProdQuery = $this->applyDateFilter($incomingProdQuery, $request);

                //     $incomingProd = $incomingProdQuery
                //         ->selectRaw('SUM(requested_qty - received_qty) AS incoming')
                //         ->value('incoming');

                //     return number_format($incomingProd ?? 0, 0, ',', '.');
                // })(),
                'incoming_stock_production' => (function () use ($product, $request) {

                    // 1️⃣ Dari material_request_items
                    $incomingMaterialQuery = DB::table('material_request_items')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at');

                    $incomingMaterialQuery = $this->applyDateFilter($incomingMaterialQuery, $request);

                    $incomingMaterial = $incomingMaterialQuery
                        ->selectRaw('SUM(requested_qty - received_qty) AS incoming')
                        ->value('incoming');

                    // 2️⃣ Dari inventory_items_2 (purchase → production warehouse)
                    $incomingInventoryQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at')
                        ->whereNotNull('purchase_item_id')
                        ->whereNotNull('production_warehouse_id');

                    $incomingInventoryQuery = $this->applyDateFilter($incomingInventoryQuery, $request);

                    $incomingInventory = $incomingInventoryQuery
                        ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                        ->value('incoming');

                    $totalIncoming = ($incomingMaterial ?? 0) + ($incomingInventory ?? 0);

                    return number_format($totalIncoming, 0, ',', '.');
                })(),
                'incoming_stock_production_completed' => (function () use ($product, $request) {

                    // 1️⃣ Material request yang sudah diterima
                    $incomingMaterialQuery = DB::table('material_request_items')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at');

                    $incomingMaterialQuery = $this->applyDateFilter($incomingMaterialQuery, $request);

                    $incomingMaterial = $incomingMaterialQuery
                        ->selectRaw('SUM(received_qty) AS incoming')
                        ->value('incoming');

                    // 2️⃣ Inventory masuk ke production warehouse
                    $incomingInventoryQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNull('deleted_at')
                        ->whereNotNull('purchase_item_id')
                        ->whereNotNull('production_warehouse_id');

                    $incomingInventoryQuery = $this->applyDateFilter($incomingInventoryQuery, $request);

                    $incomingInventory = $incomingInventoryQuery
                        ->selectRaw('SUM(stock_in) AS incoming')
                        ->value('incoming');

                    $totalCompleted = ($incomingMaterial ?? 0) + ($incomingInventory ?? 0);

                    return number_format($totalCompleted, 0, ',', '.');
                })(),

                // Pending Waiting List
                'pending_waiting_list' => (function () use ($product) {

                    $productId = $product->id;

                    $totalDesignQty = \App\Models\DesignItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_conversion_value, 1)), 0) as total')
                        ->value('total');

                    $totalAssignedQty = \App\Models\OrderProgressAssign::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('assigned_quantity');

                    $pending = $totalDesignQty - $totalAssignedQty;
                    if ($pending < 0) $pending = 0;

                    return number_format($pending, 0, ',', '.');
                })(),
                // Assigned Minus Completed
                'assigned_minus_completed' => (function () use ($product) {

                    $productId = $product->id;

                    $assigned = \App\Models\OrderProgressAssign::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('assigned_quantity');

                    $completed = \App\Models\OrderProgressItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('completed_quantity');

                    $result = $assigned - $completed;
                    if ($result < 0) $result = 0;

                    return number_format($result, 0, ',', '.');
                })(),

                'assigned_total' => (function () use ($product, $request) {

                    $query = \App\Models\OrderProgressAssign::where('product_id', $product->id)
                        ->whereNull('deleted_at');

                    // ➕ Apply date filter (menggunakan function applyDateFilter)
                    $query = $this->applyDateFilter($query, $request);

                    $assigned = $query->sum('assigned_quantity');

                    return number_format($assigned ?? 0, 0, ',', '.');
                })(),
                'on_delivery' => (function () use ($product) {

                    $productId = $product->id;

                    $totalOrderShipped = \App\Models\DeliveryOrderItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->sum('shipped_qty');

                    $totalListShipped = \App\Models\DeliveryListItem::where('product_id', $productId)
                        ->whereNull('deleted_at')
                        ->whereHas('shipment', function ($q) {
                            $q->where('status', 'finished');
                        })
                        ->sum('shipped_quantity');

                    $onDelivery = $totalOrderShipped - $totalListShipped;

                    if ($onDelivery < 0) {
                        $onDelivery = 0;
                    }

                    return number_format($onDelivery, 0, ',', '.');
                })(),
                'completed_delivery' => (function () use ($product, $request) {

                    $query = \App\Models\DeliveryListItem::where('product_id', $product->id)
                        ->whereNull('deleted_at')
                        ->whereHas('shipment', function ($q) {
                            $q->where('status', 'finished');
                        });

                    // 🔥 KALAU MAU FILTER TANGGAL → PAKAI YANG SAMA
                    $query = $this->applyDateFilter($query, $request);

                    // $completed = $query
                    //     ->selectRaw('COALESCE(SUM(shipped_quantity * COALESCE(unit_conversion_value, 1)), 0) as total')
                    //     ->value('total');

                    $completed = $query->sum('shipped_quantity');

                    return number_format($completed ?? 0, 0, ',', '.');
                })(),

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
                'inventory_stock',
                'opening_stock_warehouse',
                'production_opening_stock',
                'incoming_stock',
                'outgoing_stock',
                'incoming_stock_completed',
                'outgoing_stock_completed',
                'incoming_stock_production_completed',
                'pending_waiting_list',
                'assigned_minus_completed',
                'on_delivery',
            ])
            ->make(true);
    }
}
