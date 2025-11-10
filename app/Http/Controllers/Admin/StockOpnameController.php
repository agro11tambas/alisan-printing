<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Products;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
{
    public function getStockOpname()
    {
        return view('erp.pages.stock-opname.stock-opname');
    }

    public function dataStockOpname(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $stockOpname = StockOpname::with('product');

        // ✅ Filter tanggal
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

        // ✅ Filter status
        if ($request->has('status') && $request->status != '') {
            $stockOpname->where('status', $request->status);
        }

        // ✅ Total count sebelum paginasi
        $totalQuery = clone $stockOpname;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $stockOpname->latest()->skip($start)->take($length)->get();

        // ✅ Format JSON ringan untuk lazy load
        return response()->json([
            'data' => $data->map(function ($item) {
                $date = Carbon::parse($item->created_at)->format('d M Y H:i:s');
                $status = strtolower($item->status);
                $badge = match ($status) {
                    'gain' => '<div class="badge bg-soft-success text-success">' . e($item->status) . '</div>',
                    'loss' => '<div class="badge bg-soft-danger text-danger">' . e($item->status) . '</div>',
                    default => '<div class="badge bg-soft-primary text-primary">' . e($item->status) . '</div>',
                };

                return [
                    'id' => $item->id,
                    'product_name' => e($item->product->name ?? '-'),
                    'date' => $date,
                    'quantity' => number_format($item->quantity, 0, ',', '.'),
                    'status' => $badge,
                    'notes' => e($item->notes ?? '-'),
                    'action' => view('erp.pages.stock-opname.partials.action-button', [
                        'stockOpname' => $item
                    ])->render(),
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create()
    {
        $products = Products::orderBy('name', 'asc')->get();
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
                'stock_after_sales' => $inventoryStock->stock_after_sales + $diff,
            ]);

            // Update total stok produk
            $totalInventory = InventoryStock::where('product_id', $item['product_id'])->sum('inventory_stock');
            $totalAfterSales = InventoryStock::where('product_id', $item['product_id'])->sum('stock_after_sales');

            $product->update([
                'inventory_stock'   => $totalInventory,
                'stock_after_sales' => $totalAfterSales,
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

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'product'  => 'required|exists:products,id',
    //         'inventory_warehouse_id' => 'required|exists:inventory_warehouses,id',
    //         'date'     => 'required|date',
    //         'quantity' => 'required|integer|min:0',
    //         'notes'    => 'nullable|string',
    //     ]);

    //     // Ambil stock opname lama
    //     $stockOpname = StockOpname::findOrFail($id);

    //     // Ambil stok per warehouse
    //     $inventoryStock = InventoryStock::firstOrCreate(
    //         [
    //             'product_id'             => $request->product,
    //             'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //         ],
    //         [
    //             'opening_stock'     => 0,
    //             'opening_rate'      => 0,
    //             'inventory_stock'   => 0,
    //             'incoming_stock'    => 0,
    //             'stock_after_sales' => 0,
    //             'avg_cost'          => 0,
    //         ]
    //     );

    //     $oldStock = (int) $inventoryStock->inventory_stock;

    //     // 1. Revert efek opname lama
    //     if ($stockOpname->status === 'Gain') {
    //         $oldStock -= $stockOpname->quantity;
    //     } else { // Loss
    //         $oldStock += $stockOpname->quantity;
    //     }

    //     // 2. Hitung efek opname baru
    //     if ($request->status === 'Gain') {
    //         $newStock = $oldStock + $request->quantity;
    //         $diff     = $request->quantity;
    //     } else { // Loss
    //         $newStock = max(0, $oldStock - $request->quantity);
    //         $diff     = -$request->quantity;
    //     }

    //     // 3. Update inventory_stocks
    //     $inventoryStock->update([
    //         'inventory_stock'   => $newStock,
    //         'stock_after_sales' => $newStock,
    //     ]);

    //     // 4. Update stok global produk
    //     $totalStock = InventoryStock::where('product_id', $request->product)->sum('inventory_stock');
    //     $product    = Products::findOrFail($request->product);
    //     $product->update([
    //         'inventory_stock'   => $totalStock,
    //         'stock_after_sales' => $totalStock,
    //     ]);

    //     // 5. Update record opname
    //     $stockOpname->update([
    //         'product_id'             => $request->product,
    //         'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //         'date'       => $request->date,
    //         'quantity'   => $request->quantity,
    //         'old_stock'  => $oldStock,
    //         'diff'       => $diff,
    //         'status'     => $request->status,
    //         'notes'      => $request->notes,
    //     ]);

    //     return redirect('/erp/inventory/stock-opname')
    //         ->with('success', 'Stock Opname updated successfully.');
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product'                 => 'required|exists:products,id',
            'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
            'date'                    => 'required|date',
            'quantity'                => 'required|integer|min:0',
            'status'                  => 'required|in:Gain,Loss',
            'notes'                   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
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

            // Simpan stok awal sebelum revert
            $beforeRevertInventory = (int) $inventoryStock->inventory_stock;
            $beforeRevertAfterSales = (int) $inventoryStock->stock_after_sales;

            // 1️⃣ Revert efek opname lama
            if ($stockOpname->status === 'Gain') {
                $inventoryStock->inventory_stock   -= $stockOpname->quantity;
                $inventoryStock->stock_after_sales -= $stockOpname->quantity;
            } else { // Loss
                $inventoryStock->inventory_stock   += $stockOpname->quantity;
                $inventoryStock->stock_after_sales += $stockOpname->quantity;
            }

            // Pastikan gak minus
            $inventoryStock->inventory_stock   = max(0, $inventoryStock->inventory_stock);
            $inventoryStock->stock_after_sales = max(0, $inventoryStock->stock_after_sales);

            // Simpan hasil revert
            $inventoryStock->save();

            $oldStock = (int) $inventoryStock->inventory_stock;

            // 2️⃣ Hitung efek opname baru
            if ($request->status === 'Gain') {
                $diff     = (int) $request->quantity;
                $newStock = $oldStock + $diff;
            } else { // Loss
                $diff     = -(int) $request->quantity;
                $newStock = max(0, $oldStock + $diff);
            }

            // 3️⃣ Update inventory_stocks
            $inventoryStock->update([
                'inventory_stock'   => $newStock,
                'stock_after_sales' => max(0, $inventoryStock->stock_after_sales + $diff),
            ]);

            // 4️⃣ Update stok global produk
            $product = Products::findOrFail($request->product);
            $totalInventory  = InventoryStock::where('product_id', $request->product)->sum('inventory_stock');
            $totalAfterSales = InventoryStock::where('product_id', $request->product)->sum('stock_after_sales');

            $product->update([
                'inventory_stock'   => $totalInventory,
                'stock_after_sales' => $totalAfterSales,
            ]);

            // 5️⃣ Update record opname
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

            DB::commit();

            return redirect('/erp/inventory/stock-opname')
                ->with('success', 'Stock Opname updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Stock Opname: ' . $e->getMessage());
        }
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
