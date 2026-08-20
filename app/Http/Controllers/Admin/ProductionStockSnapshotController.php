<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use App\Models\Products;
use Illuminate\Http\Request;

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
        $length = max(1, min(50, (int) $request->input('length', 50)));
        $start = max(0, (int) $request->input('start', 0));
        $date = $request->filled('snapshot_date')
            ? $request->snapshot_date
            : today()->toDateString();
        $isToday = $date === today()->toDateString();

        $stocks = ProductionStock::query()
            ->whereHas('product', fn ($query) => $query->whereNull('products.deleted_at'))
            ->with('product')
            ->when($request->filled('product_name'), function ($query) use ($request) {
                $keyword = $request->product_name;
                $query->whereHas('product', fn ($productQuery) => $productQuery
                    ->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('sku', 'like', '%'.$keyword.'%'));
            })
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->skip($start)
            ->take($length + 1)
            ->get();

        $hasMore = $stocks->count() > $length;
        $stocks = $stocks->take($length)->values();
        $productIds = $stocks->pluck('product_id');

        $snapshots = ProductionStockSnapshot::whereIn('product_id', $productIds)
            ->whereDate('snapshot_date', $date)
            ->get()
            ->keyBy('product_id');
        $previousClosings = ProductionStockSnapshot::previousClosingStocks($date);

        $stockInByProduct = $isToday
            ? ProductionStockSnapshot::stockInTodayByProduct($productIds, $date)
            : collect();
        $assignByProduct = $isToday
            ? ProductionStockSnapshot::assignTodayByProduct($productIds, $date)
            : collect();
        $stockOpnameByProduct = $isToday
            ? ProductionStockSnapshot::stockOpnameTodayByProduct($productIds, $date)
            : collect();

        $result = $stocks->map(function ($stock, $index) use ($snapshots, $previousClosings, $stockInByProduct, $assignByProduct, $stockOpnameByProduct, $date, $isToday, $start) {
            $snapshot = $snapshots->get($stock->product_id);
            $productId = $stock->product_id;
            $openingStock = $snapshot !== null
                ? (int) $snapshot->opening_stock
                : (int) ($previousClosings[$productId] ?? ($isToday ? ($stock->available_quantity ?? 0) : 0));

            if ($isToday) {
                $stockInToday = (int) ($stockInByProduct[$productId] ?? 0);
                $assignToday = (int) ($assignByProduct[$productId] ?? 0);
                $stockOpnameToday = (int) ($stockOpnameByProduct[$productId] ?? 0);
            } else {
                $stockInToday = $snapshot?->stock_in_today ?? 0;
                $assignToday = $snapshot?->assign_today ?? 0;
                $stockOpnameToday = $snapshot?->stock_opname_today ?? 0;
            }

            $closingStock = ProductionStockSnapshot::calculateClosingStock(
                $openingStock,
                (int) $assignToday,
                (int) $stockInToday,
                (int) $stockOpnameToday,
            );

            return [
                'DT_RowIndex' => $start + $index + 1,
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

        return response()->json([
            'data' => $result->values(),
            'has_more' => $hasMore,
        ]);
    }
}
