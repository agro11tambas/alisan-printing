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
    private function applyDateFilter($query, Request $request, string $column = 'created_at')
    {
        switch ($request->filter) {
            case 'today':
                return $query->whereDate($column, Carbon::today());
            case 'last_7_days':
                return $query->whereBetween($column, [Carbon::now()->subDays(7), Carbon::now()]);
            case 'this_month':
                return $query->whereMonth($column, Carbon::now()->month)
                    ->whereYear($column, Carbon::now()->year);
            case 'last_30_days':
                return $query->whereBetween($column, [Carbon::now()->subDays(30), Carbon::now()]);
            case 'year_to_date':
                return $query->whereBetween($column, [Carbon::now()->startOfYear(), Carbon::now()]);
            case 'yearly':
                return $query->whereYear($column, Carbon::now()->year);
            case 'custom':
                if ($request->start_date && $request->end_date) {
                    return $query->whereBetween($column, [$request->start_date, $request->end_date]);
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
        $totalProducts = Products::whereNull('deleted_at')->count();

        $productQuery = Products::whereNull('deleted_at')
            ->with(['productionStocks', 'inventoryStock', 'unitConversions'])
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $search = $request->product_name;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name', 'asc');

        $filteredProducts = (clone $productQuery)->count();
        $start = max(0, $request->integer('start', 0));
        $length = min(200, max(1, $request->integer('length', 200)));

        $products = $productQuery
            ->skip($start)
            ->take($length)
            ->get();
        $productIds = $products->pluck('id');

        $completedByProduct = DB::table('order_progress_items')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->selectRaw('product_id, COALESCE(SUM(completed_quantity), 0) AS total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $deliveryOrderByProduct = DB::table('delivery_order_items')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->selectRaw('product_id, COALESCE(SUM(shipped_qty), 0) AS total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $designByProduct = DB::table('design_items')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->selectRaw('product_id, COALESCE(SUM(quantity * COALESCE(unit_conversion_value, 1)), 0) AS total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $assignedByProduct = DB::table('order_progress_assigns')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->selectRaw('product_id, COALESCE(SUM(assigned_quantity), 0) AS total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $polosanProductIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereNull('order_items.deleted_at')
            ->whereNull('orders.deleted_at')
            ->where('orders.status', 'sale list')
            ->where('orders.mode', 'polosan')
            ->pluck('order_items.product_id')
            ->flip();

        $inventoryFlowQuery = DB::table('inventory_items_2')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at');
        $inventoryFlowQuery = $this->applyDateFilter($inventoryFlowQuery, $request);
        $inventoryFlowsByProduct = $inventoryFlowQuery
            ->selectRaw('product_id')
            ->selectRaw('SUM(CASE WHEN purchase_item_id IS NOT NULL AND inventory_warehouse_id IS NOT NULL THEN remaining_stock_in - stock_in ELSE 0 END) AS incoming')
            ->selectRaw('SUM(CASE WHEN purchase_item_id IS NOT NULL AND inventory_warehouse_id IS NOT NULL THEN stock_in ELSE 0 END) AS incoming_completed')
            ->selectRaw('SUM(CASE WHEN material_request_item_id IS NOT NULL THEN remaining_stock_in - stock_out - stock_in ELSE 0 END) AS outgoing')
            ->selectRaw('SUM(CASE WHEN material_request_item_id IS NOT NULL THEN stock_out - stock_in ELSE 0 END) AS outgoing_completed')
            ->selectRaw('SUM(CASE WHEN purchase_item_id IS NOT NULL AND production_warehouse_id IS NOT NULL THEN remaining_stock_in - stock_in ELSE 0 END) AS production_incoming')
            ->selectRaw('SUM(CASE WHEN purchase_item_id IS NOT NULL AND production_warehouse_id IS NOT NULL THEN stock_in ELSE 0 END) AS production_completed')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $materialFlowQuery = DB::table('material_request_items')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at');
        $materialFlowQuery = $this->applyDateFilter($materialFlowQuery, $request);
        $materialFlowsByProduct = $materialFlowQuery
            ->selectRaw('product_id, SUM(requested_qty - received_qty) AS incoming, SUM(received_qty) AS completed')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $filteredAssignQuery = DB::table('order_progress_assigns')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at');
        $filteredAssignQuery = $this->applyDateFilter($filteredAssignQuery, $request);
        $filteredAssignedByProduct = $filteredAssignQuery
            ->selectRaw('product_id, COALESCE(SUM(assigned_quantity), 0) AS total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $finishedDeliveryQuery = DB::table('delivery_list_items')
            ->join('delivery_lists', 'delivery_lists.id', '=', 'delivery_list_items.delivery_list_id')
            ->whereIn('delivery_list_items.product_id', $productIds)
            ->whereNull('delivery_list_items.deleted_at')
            ->whereNull('delivery_lists.deleted_at')
            ->where('delivery_lists.status', 'finished');
        $finishedDeliveryQuery = $this->applyDateFilter($finishedDeliveryQuery, $request, 'delivery_list_items.created_at');
        $finishedDeliveryByProduct = $finishedDeliveryQuery
            ->selectRaw('delivery_list_items.product_id, COALESCE(SUM(delivery_list_items.shipped_quantity), 0) AS total')
            ->groupBy('delivery_list_items.product_id')
            ->pluck('total', 'delivery_list_items.product_id');

        $data = $products->map(function ($product) use (
            $request,
            $completedByProduct,
            $deliveryOrderByProduct,
            $designByProduct,
            $assignedByProduct,
            $polosanProductIds,
            $inventoryFlowsByProduct,
            $materialFlowsByProduct,
            $filteredAssignedByProduct,
            $finishedDeliveryByProduct
        ) {
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

            $productId = $product->id;
            $completed = (float) ($completedByProduct[$productId] ?? 0);
            $deliveryOrderShipped = (float) ($deliveryOrderByProduct[$productId] ?? 0);
            $finishedProductStock = max(0, $completed - $deliveryOrderShipped);
            $designQuantity = (float) ($designByProduct[$productId] ?? 0);
            $assignedQuantity = (float) ($assignedByProduct[$productId] ?? 0);
            $pendingWaitingList = max(0, $designQuantity - $assignedQuantity);
            $productionAvailable = (float) ($prod->available_quantity ?? 0);
            $inventoryStock = (float) ($inv->inventory_stock ?? 0);
            $stockAfterSales = $polosanProductIds->has($productId)
                ? $inventoryStock + $productionAvailable + $pendingWaitingList
                : $inventoryStock + $productionAvailable - $pendingWaitingList;
            $inventoryFlow = $inventoryFlowsByProduct->get($productId);
            $materialFlow = $materialFlowsByProduct->get($productId);
            $productionIncoming = (float) ($materialFlow->incoming ?? 0)
                + (float) ($inventoryFlow->production_incoming ?? 0);
            $productionCompleted = (float) ($materialFlow->completed ?? 0)
                + (float) ($inventoryFlow->production_completed ?? 0);
            $assignedMinusCompleted = max(0, $assignedQuantity - $completed);
            $finishedDelivery = (float) ($finishedDeliveryByProduct[$productId] ?? 0);
            $onDelivery = max(0, $deliveryOrderShipped - $finishedDelivery);

            return [
                'name' => $product->name,
                'production_available' =>
                '<span class="text-dark">' . number_format($prod->available_quantity ?? 0, 0, ',', '.') . '</span>',
                'production_opening_stock' =>
                '<span class="text-dark">' . number_format($prod->opening_stock ?? 0, 0, ',', '.') . '</span>',
                // 'finished_product_stock' =>
                // '<span class="text-dark">' . number_format($prod->finished_product_stock ?? 0, 0, ',', '.') . '</span>',
                'finished_product_stock' => (function () use ($product, $finishedProductStock) {
                    return '<span class="text-dark">' . number_format($finishedProductStock, 0, ',', '.') . '</span>';

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
                'stock_after_sales' => (function () use ($product, $inv, $stockAfterSales) {
                    return number_format($stockAfterSales, 0, ',', '.');

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
                'incoming_stock' => (function () use ($product, $request, $inventoryFlow) {
                    return number_format($inventoryFlow->incoming ?? 0, 0, ',', '.');
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
                'incoming_stock_completed' => (function () use ($product, $request, $inventoryFlow) {
                    return number_format($inventoryFlow->incoming_completed ?? 0, 0, ',', '.');
                    $incomingQuery = DB::table('inventory_items_2')
                        ->where('product_id', $product->id)
                        ->whereNotNull(['purchase_item_id', 'inventory_warehouse_id']);

                    $incomingQuery = $this->applyDateFilter($incomingQuery, $request);

                    $incoming = $incomingQuery
                        ->selectRaw('SUM(stock_in) AS incoming')
                        ->value('incoming');


                    return number_format($incoming ?? 0, 0, ',', '.');
                })(),
                'outgoing_stock' => (function () use ($product, $request, $inventoryFlow) {
                    return number_format($inventoryFlow->outgoing ?? 0, 0, ',', '.');
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
                'outgoing_stock_completed' => (function () use ($product, $request, $inventoryFlow) {
                    return number_format($inventoryFlow->outgoing_completed ?? 0, 0, ',', '.');
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
                'incoming_stock_production' => (function () use ($product, $request, $productionIncoming) {
                    return number_format($productionIncoming, 0, ',', '.');

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
                'incoming_stock_production_completed' => (function () use ($product, $request, $productionCompleted) {
                    return number_format($productionCompleted, 0, ',', '.');

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
                'pending_waiting_list' => (function () use ($product, $pendingWaitingList) {
                    return number_format($pendingWaitingList, 0, ',', '.');

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
                'assigned_minus_completed' => (function () use ($product, $assignedMinusCompleted) {
                    return number_format($assignedMinusCompleted, 0, ',', '.');

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

                'assigned_total' => (function () use ($product, $request, $filteredAssignedByProduct) {
                    return number_format($filteredAssignedByProduct[$product->id] ?? 0, 0, ',', '.');

                    $query = \App\Models\OrderProgressAssign::where('product_id', $product->id)
                        ->whereNull('deleted_at');

                    // ➕ Apply date filter (menggunakan function applyDateFilter)
                    $query = $this->applyDateFilter($query, $request);

                    $assigned = $query->sum('assigned_quantity');

                    return number_format($assigned ?? 0, 0, ',', '.');
                })(),
                'on_delivery' => (function () use ($product, $onDelivery) {
                    return number_format($onDelivery, 0, ',', '.');

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
                'completed_delivery' => (function () use ($product, $request, $finishedDelivery) {
                    return number_format($finishedDelivery, 0, ',', '.');

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
        })->values()->map(function (array $row, int $index) use ($start) {
            $row['DT_RowIndex'] = $start + $index + 1;

            return $row;
        });

        return response()->json([
            'draw' => $request->integer('draw', 0),
            'recordsTotal' => $totalProducts,
            'recordsFiltered' => $filteredProducts,
            'data' => $data,
        ]);
    }
}
