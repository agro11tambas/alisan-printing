<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Products;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
{
    public function getStockOpname()
    {
        return view('erp.pages.stock-opname.stock-opname');
    }

    public function dataStockOpname(Request $request)
    {
        $stockOpname = StockOpname::with('product');

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $stockOpname->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $stockOpname->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $stockOpname->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $stockOpname->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $stockOpname->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $stockOpname->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $stockOpname->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        if ($request->has('status') && $request->status != '') {
            $stockOpname->where('status', $request->status);
        }

        $stockOpname = $stockOpname->latest()->get();

        return DataTables::of($stockOpname)
            ->addIndexColumn()
            ->addColumn('product_name', function ($stockOpname) {
                return $stockOpname->product->name ?? '-';
            })
            ->addColumn('date', function ($stockOpname) {
                return $stockOpname->date;
            })
            ->addColumn('quantity', function ($stockOpname) {
                return $stockOpname->quantity;
            })
            ->addColumn('status', function ($stockOpname) {
                $status = strtolower($stockOpname->status);

                switch ($status) {
                    case 'gain':
                        return '<div class="badge bg-soft-success text-success">' . $stockOpname->status . '</div>';
                    case 'loss':
                        return '<div class="badge bg-soft-danger text-danger">' . $stockOpname->status . '</div>';
                    default:
                        return '<div class="badge bg-soft-primary text-primary">' . $stockOpname->status . '</div>';
                }
            })
            ->addColumn('notes', function ($stockOpname) {
                return $stockOpname->notes ?? '-';
            })
            ->addColumn('action', function ($stockOpname) {
                return view('erp.pages.stock-opname.partials.action-button', compact('stockOpname'))->render();
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::all();
        return view('erp.pages.stock-opname.create-stock-opname', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                              => 'required|array|min:1',
            'items.*.product_id'                 => 'required|exists:products,id',
            'items.*.inventory_warehouse_id'     => 'required|exists:inventory_warehouses,id',
            'items.*.date'                       => 'required|date',
            'items.*.quantity'                   => 'required|integer|min:0',
            'items.*.status'                     => 'required|in:Gain,Loss',
            'items.*.notes'                      => 'nullable|string',
        ]);

        foreach ($request->items as $item) {
            $product = Products::findOrFail($item['product_id']);

            $inventoryStock = InventoryStock::firstOrCreate(
                [
                    'product_id'             => $item['product_id'],
                    'inventory_warehouse_id' => $item['inventory_warehouse_id'],
                ],
                [
                    'opening_stock'     => 0,
                    'opening_rate'      => 0,
                    'inventory_stock'   => 0,
                    'incoming_stock'    => 0,
                    'stock_after_sales' => 0,
                    'avg_cost'          => 0,
                ]
            );

            $oldStock = (int) $inventoryStock->inventory_stock;

            if ($item['status'] === 'Gain') {
                $diff = (int) $item['quantity'];
                $newStock = $oldStock + $diff;
            } else {
                $diff = -(int) $item['quantity'];
                $newStock = max(0, $oldStock + $diff);
            }

            // Simpan history
            StockOpname::create([
                'product_id'             => $item['product_id'],
                'inventory_warehouse_id' => $item['inventory_warehouse_id'],
                'date'       => $item['date'],
                'quantity'   => $item['quantity'],
                'old_stock'  => $oldStock,
                'diff'       => $diff,
                'status'     => $item['status'],
                'notes'      => $item['notes'] ?? null,
            ]);

            // Update stok per warehouse
            $inventoryStock->update([
                'inventory_stock'   => $newStock,
                'stock_after_sales' => $newStock,
            ]);

            // Update total stok produk
            $totalStock = InventoryStock::where('product_id', $item['product_id'])->sum('inventory_stock');
            $product->update([
                'inventory_stock'   => $totalStock,
                'stock_after_sales' => $totalStock,
            ]);
        }

        return redirect('/erp/inventory/stock-opname')
            ->with('success', 'Stock Opname created successfully.');
    }

    public function edit($id)
    {
        $stockOpname = StockOpname::findOrFail($id);

        $products = Products::all();

        return view('erp.pages.stock-opname.edit-stock-opname', compact('stockOpname', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product'  => 'required|exists:products,id',
            'inventory_warehouse_id' => 'required|exists:inventory_warehouses,id',
            'date'     => 'required|date',
            'quantity' => 'required|integer|min:0',
            'notes'    => 'nullable|string',
        ]);

        // Ambil stock opname lama
        $stockOpname = StockOpname::findOrFail($id);

        // Ambil stok per warehouse
        $inventoryStock = InventoryStock::firstOrCreate(
            [
                'product_id'             => $request->product,
                'inventory_warehouse_id' => $request->inventory_warehouse_id,
            ],
            [
                'opening_stock'     => 0,
                'opening_rate'      => 0,
                'inventory_stock'   => 0,
                'incoming_stock'    => 0,
                'stock_after_sales' => 0,
                'avg_cost'          => 0,
            ]
        );

        $oldStock = (int) $inventoryStock->inventory_stock;

        // 1. Revert efek opname lama
        if ($stockOpname->status === 'Gain') {
            $oldStock -= $stockOpname->quantity;
        } else { // Loss
            $oldStock += $stockOpname->quantity;
        }

        // 2. Hitung efek opname baru
        if ($request->status === 'Gain') {
            $newStock = $oldStock + $request->quantity;
            $diff     = $request->quantity;
        } else { // Loss
            $newStock = max(0, $oldStock - $request->quantity);
            $diff     = -$request->quantity;
        }

        // 3. Update inventory_stocks
        $inventoryStock->update([
            'inventory_stock'   => $newStock,
            'stock_after_sales' => $newStock,
        ]);

        // 4. Update stok global produk
        $totalStock = InventoryStock::where('product_id', $request->product)->sum('inventory_stock');
        $product    = Products::findOrFail($request->product);
        $product->update([
            'inventory_stock'   => $totalStock,
            'stock_after_sales' => $totalStock,
        ]);

        // 5. Update record opname
        $stockOpname->update([
            'product_id'             => $request->product,
            'inventory_warehouse_id' => $request->inventory_warehouse_id,
            'date'       => $request->date,
            'quantity'   => $request->quantity,
            'old_stock'  => $oldStock,
            'diff'       => $diff,
            'status'     => $request->status,
            'notes'      => $request->notes,
        ]);

        return redirect('/erp/inventory/stock-opname')
            ->with('success', 'Stock Opname updated successfully.');
    }

    public function delete($id)
    {
        // Ambil stock opname yang mau dihapus
        $stockOpname = StockOpname::findOrFail($id);

        // Ambil inventory stock (per warehouse)
        $inventoryStock = InventoryStock::where('product_id', $stockOpname->product_id)
            ->where('inventory_warehouse_id', $stockOpname->inventory_warehouse_id)
            ->first();

        if ($inventoryStock) {
            $currentStock = (int) $inventoryStock->inventory_stock;

            // Revert efek opname yang mau dihapus
            if ($stockOpname->status === 'Gain') {
                $newStock = max(0, $currentStock - $stockOpname->quantity);
            } else { // Loss
                $newStock = $currentStock + $stockOpname->quantity;
            }

            // Update stok di warehouse
            $inventoryStock->update([
                'inventory_stock'   => $newStock,
                'stock_after_sales' => $newStock,
            ]);

            // Update stok global produk
            $totalStock = InventoryStock::where('product_id', $stockOpname->product_id)->sum('inventory_stock');
            $product = Products::findOrFail($stockOpname->product_id);
            $product->update([
                'inventory_stock'   => $totalStock,
                'stock_after_sales' => $totalStock,
            ]);
        }

        // Hapus record stock opname
        $stockOpname->delete();

        return redirect('/erp/inventory/stock-opname')
            ->with('success', 'Stock Opname deleted successfully.');
    }
}
