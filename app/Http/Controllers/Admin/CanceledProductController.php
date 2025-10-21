<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CanceledProduct;
use App\Models\CanceledProductHistory;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\ProductionStock;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class CanceledProductController extends Controller
{
    public function getCanceledProducts()
    {
        return view('erp.pages.adjustment-products.canceled-products.canceled-products');
    }

    public function dataCanceledProducts(Request $request)
    {
        $canceledProducts = ProductionStock::with('product');

        if ($request->filled('product_name')) {
            $canceledProducts->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        $canceledProducts->orderBy(
            Products::select('name')
                ->whereColumn('products.id', 'production_stocks.product_id')
        );

        $canceledProducts = $canceledProducts->get();

        return DataTables::of($canceledProducts)
            ->addIndexColumn()
            ->addColumn('name', function ($canceledProduct) {
                return $canceledProduct->product->name;
            })
            ->addColumn('canceled_product_stock', function ($canceledProduct) {
                return '<span class="text-danger">' . number_format($canceledProduct->canceled_product_stock) . '</span>';
            })
            ->addColumn('action', function ($canceledProduct) {
                return view(
                    'erp.pages.adjustment-products.canceled-products.partials.action-button',
                    compact('canceledProduct')
                )->render();
            })
            ->rawColumns(['canceled_product_stock', 'action'])
            ->make(true);
    }

    public function detailCanceledProducts($id)
    {
        $productionStock = ProductionStock::with('product')->findOrFail($id);

        return view('erp.pages.adjustment-products.canceled-products.detail-canceled-product', compact('productionStock'));
    }

    public function dataDetailCanceledProducts(Request $request, $id)
    {
        $canceledRecords = CanceledProduct::with('user')
            ->where('production_stock_id', $id)
            ->orderBy('date', 'desc');

        return DataTables::of($canceledRecords)
            ->addIndexColumn()
            ->addColumn('date', fn($record) => $record->date->format('Y-m-d'))
            ->addColumn('quantity', fn($record) => '<span class="text-danger fw-bold">' . number_format($record->quantity) . '</span>')
            ->addColumn('type', fn($record) => $record->type ?? '-')
            ->addColumn('note', fn($record) => $record->note ?? '-')
            ->addColumn('user', fn($record) => $record->user?->name ?? '-')
            ->addColumn('status', function ($record) {
                if ($record->status === 'completed') {
                    return '<span class="badge bg-soft-success text-success">Completed</span>';
                }
                return '<span class="badge bg-soft-warning text-warning">Pending</span>';
            })
            ->addColumn('action', function ($record) {
                return view(
                    'erp.pages.adjustment-products.canceled-products.partials.action-button-detail',
                    compact('record')
                )->render();
            })
            ->rawColumns(['quantity', 'action', 'status'])
            ->make(true);
    }

    public function returnToWarehouse(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'canceled_product' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $canceledRecord = CanceledProduct::with('stock')->findOrFail($id);

            $saleReturnOrderNumber = null;
            if ($canceledRecord->sale_return_id) {
                $saleReturnOrderNumber = \App\Models\SaleReturn::where('id', $canceledRecord->sale_return_id)
                    ->value('order_number');
            }

            $productionStock = $canceledRecord->stock;

            $qty = (int) $request->canceled_product;

            if ($canceledRecord->quantity < $qty) {
                return back()->with('error', 'Jumlah return melebihi stok canceled product record!');
            }

            // Buat inventory (link ke canceled product)
            $inventory = Inventory::create([
                'date'                => $request->date,
                // 'order_id'            => $canceledRecord->order_id,
                // 'sale_return_id'      => $canceledRecord->sale_return_id,
                'canceled_product_id' => $canceledRecord->id, // ← sudah ditambah di migrations
                'order_number'       => $saleReturnOrderNumber,
                'status'              => 'Stock In',
                'note'                => 'Return Canceled Product to Warehouse',
            ]);

            // Buat inventory item
            InventoryItem::create([
                'inventory_warehouse_id' => $request->warehouse_id ?? 1,
                'inventory_id'        => $inventory->id,
                'product_id'          => $canceledRecord->product_id,
                // 'order_item_id'       => $canceledRecord->order_item_id,
                // 'sale_return_item_id' => $canceledRecord->sale_return_item_id,
                'quantity'            => $qty,
                'stock_in'            => 0,
                'remaining_stock_in'  => $qty,
                'stock_out'           => 0,
            ]);

            // Update stok
            if ($productionStock) {
                $productionStock->decrement('canceled_product_stock', $qty);
            }
            $canceledRecord->decrement('quantity', $qty);
            $canceledRecord->increment('completed_quantity', $qty);

            if ($canceledRecord->quantity <= 0) {
                $canceledRecord->update([
                    'status' => 'completed'
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Produk berhasil dikembalikan ke warehouse.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return canceled product gagal: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengembalikan produk: ' . $e->getMessage());
        }
    }

    public function getCanceledProductHistory($id)
    {
        $productionStock = ProductionStock::with('product')->findOrFail($id);
        return view('erp.pages.adjustment-products.canceled-products.history-canceled-products', compact('productionStock'));
    }

    public function dataCanceledProductHistory(Request $request, $id)
    {
        $histories = CanceledProductHistory::with(['user', 'warehouse'])
            ->where('production_stock_id', $id);

        // filter by date
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $histories->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $histories->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $histories->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $histories->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $histories->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $histories->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $histories->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time
                    break;
            }
        }

        $histories = $histories->latest()->get();

        return DataTables::of($histories)
            ->addIndexColumn()
            ->addColumn('date', fn($h) => $h->date->format('Y-m-d'))
            ->addColumn('quantity', fn($h) => '<span class="fw-bold text-danger">' . $h->quantity . '</span>')
            ->addColumn('warehouse', fn($h) => $h->warehouse?->name ?? '-')
            ->addColumn('note', fn($h) => $h->note ?? '-')
            ->addColumn('user', fn($h) => $h->user?->name ?? '-')
            ->rawColumns(['quantity'])
            ->make(true);
    }
}
