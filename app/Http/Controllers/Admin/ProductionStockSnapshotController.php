<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use App\Models\Products;
use App\Models\StockOpnameProduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductionStockSnapshotController extends Controller
{
    public function getSnapshotReport()
    {
        return view('erp.pages.production.snapshot-report.snapshot-report');
    }

    // public function dataSnapshotReport(Request $request)
    // {
    //     $date = $request->filled('snapshot_date')
    //         ? $request->snapshot_date
    //         : today()->toDateString();

    //     // Query dari ProductionStock supaya semua produk aktif tampil
    //     $stocks = ProductionStock::query()
    //         ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
    //         ->with('product')
    //         ->when($request->filled('product_name'), function ($q) use ($request) {
    //             $keyword = $request->product_name;
    //             $q->whereHas('product', fn($q2) => $q2->where('name', 'like', '%' . $keyword . '%')
    //                 ->orWhere('sku', 'like', '%' . $keyword . '%'));
    //         })
    //         ->orderBy(
    //             Products::select('name')
    //                 ->whereColumn('products.id', 'production_stocks.product_id')
    //         )
    //         ->get();

    //     // Load snapshot tanggal itu, index by product_id
    //     $snapshots = ProductionStockSnapshot::whereDate('snapshot_date', $date)
    //         ->get()
    //         ->keyBy('product_id');

    //     // Merge — produk tanpa snapshot nilainya 0
    //     $result = $stocks->map(function ($stock) use ($snapshots, $date) {
    //         $snap = $snapshots->get($stock->product_id);
    //         return [
    //             'product_name'   => $stock->product->name ?? '-',
    //             'available_quantity' => number_format($stock->available_quantity ?? 0, 0, ',', '.'),
    //             'snapshot_date'  => \Carbon\Carbon::parse($date)->format('d/m/Y'),
    //             'opening_stock'  => number_format($snap?->opening_stock ?? 0, 0, ',', '.'),
    //             'closing_stock'  => number_format($snap?->closing_stock ?? 0, 0, ',', '.'),
    //             'stock_in_today' => number_format($snap?->stock_in_today ?? 0, 0, ',', '.'),
    //             'assign_today'   => number_format($snap?->assign_today ?? 0, 0, ',', '.'),
    //         ];
    //     });

    //     return DataTables::of($result)
    //         ->addIndexColumn()
    //         ->make(true);
    // }

    public function dataSnapshotReport(Request $request)
    {
        $date = $request->filled('snapshot_date')
            ? $request->snapshot_date
            : today()->toDateString();

        $isToday = $date === today()->toDateString(); // 🔥

        $stocks = ProductionStock::query()
            ->whereHas('product', fn ($q) => $q->whereNull('products.deleted_at'))
            ->with('product')
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $keyword = $request->product_name;
                $q->whereHas('product', fn ($q2) => $q2->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('sku', 'like', '%'.$keyword.'%'));
            })
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->get();

        $snapshots = ProductionStockSnapshot::whereDate('snapshot_date', $date)
            ->get()
            ->keyBy('product_id');

        $result = $stocks->map(function ($stock) use ($snapshots, $date, $isToday) {
            $snap = $snapshots->get($stock->product_id);
            $productId = $stock->product_id;

            if ($isToday) {
                // 🔥 Real-time, sama persis kayak dataReportItems

                $fromMaterial = DB::table('material_request_items')
                    ->where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $date)
                    ->sum('received_qty');

                $fromInventory = DB::table('inventory_stock_in_histories_2 as h')
                    ->join('inventory_stock_ins_2 as s', 's.id', '=', 'h.inventory_stock_in_id')
                    ->join('inventory_items_2 as i', 'i.id', '=', 'h.inventory_item_id')
                    ->join('inventories_2 as inv', 'inv.id', '=', 'i.inventory_id')
                    ->where('i.product_id', $productId)
                    ->where('inv.status', 'Stock In Production')
                    ->whereNull('h.deleted_at')
                    ->whereNull('s.deleted_at')
                    ->whereDate('s.created_at', $date)
                    ->sum('h.stock_in');

                $stockInToday = ($fromMaterial ?? 0) + ($fromInventory ?? 0);

                $assignToday = \App\Models\OrderProgressAssign::where('product_id', $productId)
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $date)
                    ->sum('assigned_quantity');

                $stockOpnameToday = StockOpnameProduction::where('product_id', $productId)
                    ->whereDate('date', $date)
                    ->get()
                    ->sum(fn (StockOpnameProduction $stockOpname) => $stockOpname->signedQuantity());

                $openingStock = $snap?->opening_stock ?? 0; // tetap dari snapshot kalau ada
                $closingStock = $stock->available_quantity ?? 0; // real-time
            } else {
                // 📦 Dari snapshot tersimpan
                $stockInToday = $snap?->stock_in_today ?? 0;
                $assignToday = $snap?->assign_today ?? 0;
                $stockOpnameToday = $snap?->stock_opname_today ?? 0;
                $openingStock = $snap?->opening_stock ?? 0;
                $closingStock = $snap?->closing_stock ?? 0;
            }

            return [
                'product_name' => $stock->product->name ?? '-',
                'available_quantity' => number_format($stock->available_quantity ?? 0, 0, ',', '.'),
                'snapshot_date' => \Carbon\Carbon::parse($date)->format('d/m/Y'),
                'opening_stock' => number_format($openingStock, 0, ',', '.'),
                'closing_stock' => number_format($closingStock, 0, ',', '.'),
                'stock_in_today' => number_format($stockInToday, 0, ',', '.'),
                'assign_today' => number_format($assignToday, 0, ',', '.'),
                'stock_opname_today' => number_format($stockOpnameToday, 0, ',', '.'),
            ];
        });

        return DataTables::of($result)
            ->addIndexColumn()
            ->make(true);
    }
}
