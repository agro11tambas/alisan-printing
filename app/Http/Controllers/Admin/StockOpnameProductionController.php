<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\StockOpnameProduction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameProductionController extends Controller
{
    public function getStockOpnameProduction()
    {
        return view('erp.pages.stock-opname-production.stock-opname-production');
    }

    public function dataStockOpnameProduction(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $stockOpname = StockOpnameProduction::with('product');

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

        // ✅ Hindari query count dua kali
        $totalQuery = clone $stockOpname;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $stockOpname->latest()->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy load)
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
                    'available_quantity' => number_format($item->available_quantity, 0, ',', '.'),
                    'finished_product' => number_format($item->finished_product, 0, ',', '.'),
                    'status' => $badge,
                    'notes' => e($item->notes ?? '-'),
                    'action' => view('erp.pages.stock-opname-production.partials.action-button', [
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
        return view('erp.pages.stock-opname-production.create-stock-opname-production', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                           => 'required|array|min:1',
            'items.*.product_id'              => 'required|exists:products,id',
            'items.*.production_warehouse_id' => 'required|exists:production_warehouses,id',
            'items.*.date'                    => 'required|date',
            'items.*.available_quantity'      => 'required|numeric|min:0',
            'items.*.status'                  => 'required|in:Gain,Loss',
            'items.*.notes'                   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                // Skip jika qty kosong atau nol
                if (empty($item['available_quantity']) || $item['available_quantity'] == 0) {
                    continue;
                }

                // 🔹 Cari atau buat stok produksi
                $productionStock = ProductionStock::firstOrCreate(
                    [
                        'product_id'              => $item['product_id'],
                        'production_warehouse_id' => $item['production_warehouse_id'],
                    ],
                    [
                        'opening_stock'          => 0,
                        'finished_product_stock' => 0,
                        'available_quantity'     => 0,
                    ]
                );

                $oldAvailable = (int) $productionStock->available_quantity;
                $qty          = (int) $item['available_quantity'];
                $newAvailable = $item['status'] === 'Gain'
                    ? $oldAvailable + $qty
                    : max(0, $oldAvailable - $qty);

                // 🔹 Simpan history opname production
                StockOpnameProduction::create([
                    'product_id'              => $item['product_id'],
                    'production_warehouse_id' => $item['production_warehouse_id'],
                    'date'                    => $item['date'],
                    'change'                  => 'available_quantity', // tetap isi untuk kompatibilitas kolom
                    'available_quantity'      => $qty,
                    'status'                  => $item['status'],
                    'notes'                   => $item['notes'] ?? null,
                ]);

                // 🔹 Update stok di tabel production_stocks
                $productionStock->update([
                    'available_quantity' => $newAvailable,
                ]);

                // 🔹 Update juga stock_after_sales di inventory_stocks
                $inventoryStock = InventoryStock::firstOrCreate(
                    ['product_id' => $item['product_id']],
                    [
                        'opening_stock'     => 0,
                        'opening_rate'      => 0,
                        'inventory_stock'   => 0,
                        'purchase_stock'    => 0,
                        'stock_after_sales' => 0,
                        'minimum_stock'     => 0,
                        'avg_cost'          => 0,
                    ]
                );

                $oldAfterSales = (float) $inventoryStock->stock_after_sales;
                $newAfterSales = $item['status'] === 'Gain'
                    ? $oldAfterSales + $qty
                    : max(0, $oldAfterSales - $qty);

                $inventoryStock->update(['stock_after_sales' => $newAfterSales]);
            }

            DB::commit();
            return redirect('/erp/productions/stock-opname')
                ->with('success', 'Stock Opname Production created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create Stock Opname Production: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $stockOpname = StockOpnameProduction::findOrFail($id);

            // 🔹 Ambil stok produksi terkait
            $productionStock = ProductionStock::where('product_id', $stockOpname->product_id)
                ->where('production_warehouse_id', $stockOpname->production_warehouse_id)
                ->first();

            // 🔹 Ambil stok inventory terkait
            $inventoryStock = InventoryStock::firstOrCreate(
                ['product_id' => $stockOpname->product_id],
                [
                    'opening_stock'     => 0,
                    'opening_rate'      => 0,
                    'inventory_stock'   => 0,
                    'purchase_stock'    => 0,
                    'stock_after_sales' => 0,
                    'minimum_stock'     => 0,
                    'avg_cost'          => 0,
                ]
            );

            if ($productionStock) {
                $oldAvailable = (int) $productionStock->available_quantity;
                $qty = (int) $stockOpname->available_quantity;

                // 🔁 rollback efek dari opname sebelumnya
                $newAvailable = $stockOpname->status === 'Gain'
                    ? max(0, $oldAvailable - $qty)   // jika sebelumnya Gain → sekarang dikurangi
                    : $oldAvailable + $qty;          // jika sebelumnya Loss → sekarang ditambah

                $productionStock->update([
                    'available_quantity' => $newAvailable,
                ]);
            }

            if ($inventoryStock) {
                $oldAfterSales = (float) $inventoryStock->stock_after_sales;
                $qty = (float) $stockOpname->available_quantity;

                $newAfterSales = $stockOpname->status === 'Gain'
                    ? max(0, $oldAfterSales - $qty)
                    : $oldAfterSales + $qty;

                $inventoryStock->update([
                    'stock_after_sales' => $newAfterSales,
                ]);
            }

            // 🔹 Hapus data opname
            $stockOpname->delete();

            DB::commit();

            return redirect('/erp/productions/stock-opname')
                ->with('success', 'Stock Opname deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete Stock Opname: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $stockOpname = StockOpnameProduction::findOrFail($id);

        $products = Products::all();

        return view('erp.pages.stock-opname-production.edit-stock-opname-production', compact('stockOpname', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product'                  => 'required|exists:products,id',
            'production_warehouse_id'  => 'required|exists:production_warehouses,id',
            'date'                     => 'required|date',
            'available_quantity'       => 'required|integer|min:0',
            'status'                   => 'required|in:Gain,Loss',
            'notes'                    => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // 🔹 Ambil data opname lama
            $stockOpname = StockOpnameProduction::findOrFail($id);

            // 🔹 Ambil stok produksi lama
            $productionStock = ProductionStock::where('product_id', $stockOpname->product_id)
                ->where('production_warehouse_id', $stockOpname->production_warehouse_id)
                ->first();

            // 🔹 Ambil inventory stock (untuk rollback dan update ulang)
            $inventoryStock = InventoryStock::firstOrCreate(
                ['product_id' => $stockOpname->product_id],
                [
                    'opening_stock'     => 0,
                    'opening_rate'      => 0,
                    'inventory_stock'   => 0,
                    'purchase_stock'    => 0,
                    'stock_after_sales' => 0,
                    'minimum_stock'     => 0,
                    'avg_cost'          => 0,
                ]
            );

            // =============================
            // 1️⃣ ROLLBACK efek opname lama
            // =============================
            if ($productionStock) {
                $rollbackQty = (int) $stockOpname->available_quantity;
                $rollbackAdj = $stockOpname->status === 'Gain' ? -$rollbackQty : $rollbackQty;

                $productionStock->update([
                    'available_quantity' => max(0, $productionStock->available_quantity + $rollbackAdj),
                ]);

                $inventoryStock->update([
                    'stock_after_sales' => max(0, $inventoryStock->stock_after_sales + $rollbackAdj),
                ]);
            }

            // =============================
            // 2️⃣ UPDATE data opname baru
            // =============================
            $stockOpname->update([
                'product_id'              => $request->product,
                'production_warehouse_id' => $request->production_warehouse_id,
                'date'                    => $request->date,
                'change'                  => 'available_quantity', // biar kompatibel dengan kolom DB
                'available_quantity'      => $request->available_quantity,
                'status'                  => $request->status,
                'notes'                   => $request->notes,
            ]);

            // =============================
            // 3️⃣ APPLY efek baru
            // =============================
            $productionStock = ProductionStock::firstOrCreate(
                [
                    'product_id'              => $request->product,
                    'production_warehouse_id' => $request->production_warehouse_id,
                ],
                [
                    'opening_stock'          => 0,
                    'finished_product_stock' => 0,
                    'available_quantity'     => 0,
                ]
            );

            $inventoryStock = InventoryStock::firstOrCreate(
                ['product_id' => $request->product],
                [
                    'opening_stock'     => 0,
                    'opening_rate'      => 0,
                    'inventory_stock'   => 0,
                    'purchase_stock'    => 0,
                    'stock_after_sales' => 0,
                    'minimum_stock'     => 0,
                    'avg_cost'          => 0,
                ]
            );

            $qty = (int) $request->available_quantity;
            $adj = $request->status === 'Gain' ? $qty : -$qty;

            $productionStock->update([
                'available_quantity' => max(0, $productionStock->available_quantity + $adj),
            ]);

            $inventoryStock->update([
                'stock_after_sales' => max(0, $inventoryStock->stock_after_sales + $adj),
            ]);

            DB::commit();

            return redirect('/erp/productions/stock-opname')
                ->with('success', 'Stock Opname Production updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Stock Opname: ' . $e->getMessage());
        }
    }
}
