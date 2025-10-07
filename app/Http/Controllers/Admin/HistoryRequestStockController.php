<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\MaterialRequestItemHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HistoryRequestStockController extends Controller
{
    public function getRequestStockHistory($id)
    {
        $requestStock = MaterialRequest::with([
            'items.product',
            'items.histories',
            'requestedBy'
        ])->findOrFail($id);

        return view('erp.pages.production.request-stock.history-request-stock', compact('requestStock'));
    }

    public function dataRequestStockHistory(Request $request, $id)
    {
        $requestStockHistory = MaterialRequestItemHistory::with([
            'materialRequestItem.product',
        ])->whereHas('materialRequestItem', function ($q) use ($id) {
            $q->where('material_request_id', $id);
        });

        if ($request->filter) {
            $requestStockHistory->when(true, function ($q) use ($request) {
                switch ($request->filter) {
                    case 'today':
                        $q->whereDate('date', Carbon::today());
                        break;
                    case 'last_7_days':
                        $q->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                        break;
                    case 'this_month':
                        $q->whereMonth('date', Carbon::now()->month)
                            ->whereYear('date', Carbon::now()->year);
                        break;
                    case 'last_30_days':
                        $q->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                        break;
                    case 'year_to_date':
                        $q->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                        break;
                    case 'yearly':
                        $q->whereYear('date', Carbon::now()->year);
                        break;
                    case 'custom':
                        if ($request->filled('start_date') && $request->filled('end_date')) {
                            $q->whereBetween('date', [$request->start_date, $request->end_date]);
                        }
                        break;
                }
            });
        }

        return DataTables::of($requestStockHistory)
            ->addIndexColumn()
            ->addColumn('product', function ($history) {
                return $history->materialRequestItem->product->name ?? '-';
            })
            ->addColumn('quantity', function ($history) {
                return $history->quantity;
            })
            ->addColumn('date', function ($history) {
                return $history->date ? $history->date->format('Y-m-d') : '-';
            })
            ->make(true);
    }
}
