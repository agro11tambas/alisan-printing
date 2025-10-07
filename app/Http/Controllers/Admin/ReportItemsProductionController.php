<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductionStock;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportItemsProductionController extends Controller
{
    public function getReportItems()
    {
        return view('erp.pages.production.report-items.report-items');
    }

    public function dataReportItems(Request $request)
    {
        $reportItems = ProductionStock::with('product');

        if ($request->filled('product_name')) {
            $reportItems->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        $reportItems->orderBy(
            Products::select('name')
                ->whereColumn('products.id', 'production_stocks.product_id')
        );

        $reportItems = $reportItems->get();

        return DataTables::of($reportItems)
            ->addIndexColumn()
            ->addColumn('name', function ($reportItem) {
                return $reportItem->product->name;
            })
            ->addColumn('available_quantity', function ($reportItem) {
                return '<span class="text-danger">' . $reportItem->available_quantity . '</span>';
            })
            ->addColumn('finished_product_stock', function ($reportItem) {
                return '<span class="text-primary">' . $reportItem->finished_product_stock . '</span>';
            })
            ->addColumn(
                'order_progress_remaining',
                fn($reportItem) =>
                '<span class="text-success">' . $reportItem->remaining_quantity . '</span>'
            )
            ->rawColumns(['available_quantity', 'finished_product_stock', 'order_progress_remaining'])
            ->make(true);
    }
}
