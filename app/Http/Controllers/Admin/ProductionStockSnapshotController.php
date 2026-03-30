<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use App\Models\Products;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductionStockSnapshotController extends Controller
{
    public function getSnapshotReport()
    {
        return view('erp.pages.production.snapshot-report.snapshot-report');
    }

    public function dataSnapshotReport(Request $request)
    {
        $date = $request->filled('snapshot_date')
            ? $request->snapshot_date
            : today()->toDateString();

        // Query dari ProductionStock supaya semua produk aktif tampil
        $stocks = ProductionStock::query()
            ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
            ->with('product')
            ->when($request->filled('product_name'), function ($q) use ($request) {
                $keyword = $request->product_name;
                $q->whereHas('product', fn($q2) => $q2->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%'));
            })
            ->orderBy(
                Products::select('name')
                    ->whereColumn('products.id', 'production_stocks.product_id')
            )
            ->get();

        // Load snapshot tanggal itu, index by product_id
        $snapshots = ProductionStockSnapshot::whereDate('snapshot_date', $date)
            ->get()
            ->keyBy('product_id');

        // Merge — produk tanpa snapshot nilainya 0
        $result = $stocks->map(function ($stock) use ($snapshots, $date) {
            $snap = $snapshots->get($stock->product_id);
            return [
                'product_name'   => $stock->product->name ?? '-',
                'available_quantity' => number_format($stock->available_quantity ?? 0, 0, ',', '.'),
                'snapshot_date'  => \Carbon\Carbon::parse($date)->format('d/m/Y'),
                'opening_stock'  => number_format($snap?->opening_stock ?? 0, 0, ',', '.'),
                'closing_stock'  => number_format($snap?->closing_stock ?? 0, 0, ',', '.'),
                'stock_in_today' => number_format($snap?->stock_in_today ?? 0, 0, ',', '.'),
                'assign_today'   => number_format($snap?->assign_today ?? 0, 0, ',', '.'),
            ];
        });

        return DataTables::of($result)
            ->addIndexColumn()
            ->make(true);
    }
}
