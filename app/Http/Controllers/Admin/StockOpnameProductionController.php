<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $stockOpname = StockOpnameProduction::with('product');

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
            ->addColumn('available_quantity', function ($stockOpname) {
                return $stockOpname->available_quantity;
            })
            ->addColumn('finished_product', function ($stockOpname) {
                return $stockOpname->finished_product;
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
                return view('erp.pages.stock-opname-production.partials.action-button', compact('stockOpname'))->render();
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::all();

        return view('erp.pages.stock-opname-production.create-stock-opname-production', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product'  => 'required|exists:products,id',
            'production_warehouse_id' => 'required|exists:production_warehouses,id',
            'date'     => 'required|date',
            'change'   => 'required|in:available_quantity,finished_product',
            'status'   => 'required|in:Gain,Loss',
            'notes'    => 'nullable|string',
        ]);

        // validasi tambahan sesuai change
        if ($request->change === 'available_quantity') {
            $request->validate([
                'available_quantity' => 'required|integer|min:0',
            ]);
        }

        if ($request->change === 'finished_product') {
            $request->validate([
                'finished_product' => 'required|integer|min:0',
            ]);
        }

        // Ambil stok per warehouse
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

        $oldAvailable = (int) $productionStock->available_quantity;
        $oldFinished  = (int) $productionStock->finished_product_stock;

        $newAvailable = $oldAvailable;
        $newFinished  = $oldFinished;

        if ($request->change === 'available_quantity') {
            $qty = (int) $request->available_quantity;
            $newAvailable = $request->status === 'Gain'
                ? $oldAvailable + $qty
                : max(0, $oldAvailable - $qty);
        }

        if ($request->change === 'finished_product') {
            $qty = (int) $request->finished_product;
            $newFinished = $request->status === 'Gain'
                ? $oldFinished + $qty
                : max(0, $oldFinished - $qty);
        }

        // Simpan history opname
        StockOpnameProduction::create([
            'product_id'              => $request->product,
            'production_warehouse_id' => $request->production_warehouse_id,
            'date'        => $request->date,
            'change'      => $request->change,
            'available_quantity' => $request->change === 'available_quantity' ? $request->available_quantity : null,
            'finished_product'   => $request->change === 'finished_product'   ? $request->finished_product   : null,
            'status'      => $request->status,
            'notes'       => $request->notes,
        ]);

        // Update stok
        $productionStock->update([
            'available_quantity'     => $newAvailable,
            'finished_product_stock' => $newFinished,
        ]);

        return redirect('/erp/productions/stock-opname')
            ->with('success', 'Stock Opname created successfully.');
    }

    public function delete($id)
    {
        $stockOpname = StockOpnameProduction::findOrFail($id);

        $productionStock = ProductionStock::where('product_id', $stockOpname->product_id)
            ->where('production_warehouse_id', $stockOpname->production_warehouse_id)
            ->first();

        if ($productionStock) {
            $oldAvailable = (int) $productionStock->available_quantity;
            $oldFinished  = (int) $productionStock->finished_product_stock;

            $newAvailable = $oldAvailable;
            $newFinished  = $oldFinished;

            if ($stockOpname->change === 'available_quantity' && $stockOpname->available_quantity !== null) {
                $qty = (int) $stockOpname->available_quantity;
                // balikkan efek dari store
                $newAvailable = $stockOpname->status === 'Gain'
                    ? max(0, $oldAvailable - $qty)
                    : $oldAvailable + $qty;
            }

            if ($stockOpname->change === 'finished_product' && $stockOpname->finished_product !== null) {
                $qty = (int) $stockOpname->finished_product;
                // balikkan efek dari store
                $newFinished = $stockOpname->status === 'Gain'
                    ? max(0, $oldFinished - $qty)
                    : $oldFinished + $qty;
            }

            $productionStock->update([
                'available_quantity'     => $newAvailable,
                'finished_product_stock' => $newFinished,
            ]);
        }

        $stockOpname->delete();

        return redirect('/erp/productions/stock-opname')
            ->with('success', 'Stock Opname deleted successfully.');
    }

    public function edit($id)
    {
        $stockOpname = StockOpnameProduction::findOrFail($id);

        $products = Products::all();

        return view('erp.pages.stock-opname-production.edit-stock-opname-production', compact('stockOpname', 'products'));
    }

    public function update(Request $request, $id)
    {
        // validasi umum
        $request->validate([
            'product'  => 'required|exists:products,id',
            'production_warehouse_id' => 'required|exists:production_warehouses,id',
            'date'     => 'required|date',
            'change'   => 'required|in:available_quantity,finished_product',
            'status'   => 'required|in:Gain,Loss',
            'notes'    => 'nullable|string',
        ]);

        if ($request->change === 'available_quantity') {
            $request->validate(['available_quantity' => 'required|integer|min:0']);
        }

        if ($request->change === 'finished_product') {
            $request->validate(['finished_product' => 'required|integer|min:0']);
        }

        // ambil stock opname lama
        $stockOpname = StockOpnameProduction::findOrFail($id);

        // ambil stok warehouse lama
        $productionStock = ProductionStock::where('product_id', $stockOpname->product_id)
            ->where('production_warehouse_id', $stockOpname->production_warehouse_id)
            ->first();

        if ($productionStock) {
            // rollback efek lama
            if ($stockOpname->change === 'available_quantity') {
                $rollback = $stockOpname->status === 'Gain'
                    ? -(int) $stockOpname->available_quantity
                    : (int) $stockOpname->available_quantity;

                $productionStock->update([
                    'available_quantity' => max(0, $productionStock->available_quantity + $rollback),
                ]);
            }

            if ($stockOpname->change === 'finished_product') {
                $rollback = $stockOpname->status === 'Gain'
                    ? -(int) $stockOpname->finished_product
                    : (int) $stockOpname->finished_product;

                $productionStock->update([
                    'finished_product_stock' => max(0, $productionStock->finished_product_stock + $rollback),
                ]);
            }
        }

        // update data opname dengan input baru
        $stockOpname->update([
            'product_id'              => $request->product,
            'production_warehouse_id' => $request->production_warehouse_id,
            'date'                    => $request->date,
            'change'                  => $request->change,
            'available_quantity'      => $request->change === 'available_quantity' ? $request->available_quantity : null,
            'finished_product'        => $request->change === 'finished_product' ? $request->finished_product : null,
            'status'                  => $request->status,
            'notes'                   => $request->notes,
        ]);

        // apply efek baru
        $productionStock = ProductionStock::firstOrCreate(
            [
                'product_id'              => $request->product,
                'production_warehouse_id' => $request->production_warehouse_id,
            ],
            [
                'opening_stock'           => 0,
                'finished_product_stock'  => 0,
                'available_quantity'      => 0,
            ]
        );

        if ($request->change === 'available_quantity') {
            $qty = (int) $request->available_quantity;
            $adjustment = $request->status === 'Gain' ? $qty : -$qty;

            $productionStock->update([
                'available_quantity' => max(0, $productionStock->available_quantity + $adjustment),
            ]);
        }

        if ($request->change === 'finished_product') {
            $qty = (int) $request->finished_product;
            $adjustment = $request->status === 'Gain' ? $qty : -$qty;

            $productionStock->update([
                'finished_product_stock' => max(0, $productionStock->finished_product_stock + $adjustment),
            ]);
        }

        return redirect('/erp/productions/stock-opname')
            ->with('success', 'Stock Opname updated successfully.');
    }
}
