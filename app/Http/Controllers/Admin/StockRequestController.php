<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductBundle;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class StockRequestController extends Controller
{
    public function getStockRequest()
    {
        return view('erp.pages.production.request-stocks.request-stocks');
    }

    public function dataStockRequest(Request $request)
    {
        $inventory = Inventory::with(['items.product', 'purchase.supplier', 'order.customer'])
            ->where('status', 'Stock Out');

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $inventory->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $inventory->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $inventory->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $inventory->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $inventory->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $inventory->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $inventory->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        if ($request->search_type && $request->filled('search_keyword')) {
            if ($request->search_type === 'invoice_number') {
                $inventory->where(function ($q) use ($request) {
                    $q->where('purchase_number', 'like', '%' . $request->search_keyword . '%')
                        ->orWhere('order_number', 'like', '%' . $request->search_keyword . '%');
                });
            } elseif ($request->search_type === 'partner') {
                $inventory->where(function ($q) use ($request) {
                    $q->whereHas('purchaseReturn.supplier', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });

                    $q->orWhereHas('order.customer', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });
                });
            }
        } elseif ($request->search_type === 'type' && $request->filled('search_type_dropdown')) {
            if ($request->search_type_dropdown === 'purchase_return') {
                $inventory->where('note', 'Purchase Returns');
            } elseif ($request->search_type_dropdown === 'sale') {
                $inventory->where('note', 'Sale Account');
            }
        }

        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $inventory->whereDoesntHave('items', function ($q) {
                    $q->whereColumn('stock_out_production', '<', 'quantity');
                });
            } elseif ($request->progress_status === 'progress') {
                $inventory->whereHas('items', function ($q) {
                    $q->whereColumn('stock_out_production', '<', 'quantity');
                });
            }
        }

        $inventory = $inventory->latest();

        return DataTables::of($inventory)
            ->addIndexColumn()
            ->addColumn('transaction_number', function ($inventory) {
                if ($inventory->note === 'Purchase Returns') {
                    $badge = '<span class="badge bg-soft-danger text-danger mb-1">Purchase Returns</span><br>';
                    return $badge . ($inventory->purchase_number ?? '-');
                } elseif ($inventory->note === 'Sale Account') {
                    return $inventory->order_number ?? '-';
                }
                return '-';
            })
            ->addColumn('date', function ($inventory) {
                return $inventory->date;
            })
            ->addColumn('partner_name', function ($inventory) {
                if ($inventory->note === 'Purchase Returns') {
                    return optional($inventory->purchaseReturn->supplier)->name ?? '-';
                } elseif ($inventory->note === 'Sale Account') {
                    return optional($inventory->order->customer)->name ?? '-';
                }
                return '-';
            })
            ->addColumn('stock_request', function ($inventory) {
                return view('erp.pages.production.request-stocks.partials.stock-request', compact('inventory'))->render();
            })
            ->addColumn('action', function ($inventory) {
                return view('erp.pages.production.request-stocks.partials.action-button', compact('inventory'))->render();
            })
            ->rawColumns(['action', 'stock_request', 'transaction_number'])
            ->make(true);
    }

    public function addRequestStocks($orderId)
    {
        $order = Order::with([
            'customer',
            'orderItems.product',
            'orderItems.productBundle.items.product'
        ])->findOrFail($orderId);

        return view('erp.pages.production.waiting-list.add-request-stocks', compact('order'));
    }

    public function create($inventoryId)
    {
        $inventory = Inventory::with([
            'order.customer',
            'order.orderItems.product',
            'order.orderItems.productBundle.items.product',
            'items.product'
        ])->findOrFail($inventoryId);

        return view('erp.pages.production.request-stocks.add-request-stocks', compact('inventory'));
    }

    public function requestStock($orderItemId, InventoryService $inventoryService)
    {
        $orderItem = OrderItem::with(['product', 'productBundle.items.product'])->findOrFail($orderItemId);

        try {
            $inventoryService->allocateStock($orderItem, $orderItem->quantity);
            return back()->with('success', 'Stok berhasil dialokasikan');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request, $orderId)
    {
        $order = Order::with('orderItems')->findOrFail($orderId);

        DB::beginTransaction();
        try {
            $inventory = Inventory::create([
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'date'         => $request->change_date,
                'status'       => 'Stock Out',
                'note'         => 'Sale Account',
            ]);

            foreach ($order->orderItems as $index => $item) {
                $requestQty = $request->items[$index]['request_qty'] ?? $item->quantity;

                if ($item->product_bundle_id) {
                    $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                    foreach ($bundle->items as $bundleItem) {
                        InventoryItem::create([
                            'inventory_id'      => $inventory->id,
                            'product_id'        => $bundleItem->product_id,
                            'product_bundle_id' => $item->product_bundle_id,
                            'order_item_id'     => $item->id,
                            'quantity'          => $requestQty,
                            'stock_in'          => 0,
                            'remaining_stock_in' => $requestQty,
                            'stock_out'         => 0,
                        ]);

                        // langsung kurangi stok produk
                        // $bundleItem->product->decrement('stock_after_sales', $item->quantity);
                    }
                } else {
                    InventoryItem::create([
                        'inventory_id'      => $inventory->id,
                        'product_id'        => $item->product_id,
                        'product_bundle_id' => null,
                        'order_item_id'     => $item->id,
                        'quantity'          => $requestQty,
                        'stock_in'          => 0,
                        'remaining_stock_in' => $requestQty,
                        'stock_out'         => 0,
                    ]);

                    // $item->product->decrement('stock_after_sales', $item->quantity);
                }
            }

            DB::commit();
            return redirect("/erp/productions/stock-request")->with('success', 'Stock request berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal request stock: ' . $e->getMessage());
        }
    }

    public function store2(Request $request, $inventoryId)
    {
        $inventory = Inventory::with('items')->findOrFail($inventoryId);

        DB::beginTransaction();
        try {
            // Update tanggal inventory
            $inventory->update([
                'date' => $request->change_date,
            ]);

            foreach ($inventory->items as $invItem) {
                $requestQty = $request->items[$invItem->id]['request_qty'] ?? 0;

                // Update quantity item lama
                $invItem->update([
                    'quantity'           => $invItem->quantity + $requestQty,
                    'remaining_stock_in' => $invItem->remaining_stock_in + $requestQty,
                ]);
            }

            DB::commit();
            return redirect("/erp/productions/stock-request")->with('success', 'Stock request berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update request stock: ' . $e->getMessage());
        }
    }

    public function edit($inventoryId)
    {
        $inventory = Inventory::with([
            'order.customer',
            'order.orderItems.product',
            'order.orderItems.productBundle.items.product',
            'items.product'
        ])->findOrFail($inventoryId);

        return view('erp.pages.production.request-stocks.edit-request-stocks', compact('inventory'));
    }

    public function update(Request $request, $inventoryId)
    {
        $inventory = Inventory::with('items')->findOrFail($inventoryId);

        DB::beginTransaction();
        try {
            $inventory->update([
                'date' => $request->change_date,
            ]);

            foreach ($inventory->items as $index => $invItem) {
                $requestQty = $request->items[$invItem->id]['request_qty'] ?? $invItem->quantity;

                $invItem->update([
                    'quantity' => $requestQty,
                    'remaining_stock_in' => $requestQty, // sesuaikan logic kamu
                ]);
            }

            DB::commit();
            return redirect("/erp/productions/stock-request")->with('success', 'Stock request berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update request stock: ' . $e->getMessage());
        }
    }

    public function confirm(Request $request, $inventoryId)
    {
        $inventory = Inventory::with('items')->findOrFail($inventoryId);

        DB::beginTransaction();
        try {
            foreach ($inventory->items as $invItem) {

                $stockOut = $invItem->stock_out; // simpan dulu nilai lama

                // Update stock_out_production dan reset stock_out
                $invItem->update([
                    'stock_out_production' => $stockOut,
                    'stock_out_request'    => 0,
                ]);

                // Jika terkait order_item, increment stock_out di OrderItem
                if ($invItem->order_item_id) {
                    $orderItem = OrderItem::findOrFail($invItem->order_item_id);

                    if ($orderItem->product_bundle_id) {
                        $bundleItemCount = $orderItem->productBundle->items->count() ?? 1;
                        $orderItem->increment('stock_out', $stockOut / $bundleItemCount);
                    } else {
                        $orderItem->increment('stock_out', $stockOut);
                    }
                }
            }

            DB::commit();
            return redirect("/erp/productions/stock-request")->with('success', 'Stock request berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal konfirmasi request stock: ' . $e->getMessage());
        }
    }
}
