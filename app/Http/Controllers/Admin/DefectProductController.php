<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefectProduct;
use App\Models\Products;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DefectProductController extends Controller
{
    public function getDefectProducts()
    {
        return view('erp.pages.adjustment-products.defect-products.defect-products');
    }

    public function dataDefectProducts(Request $request)
    {
        $query = DefectProduct::with('product')
            ->selectRaw('product_id, SUM(quantity) as total_defect')
            ->groupBy('product_id');

        if ($request->filled('product_name')) {
            $query->whereHas('product', fn($q) =>
            $q->where('name', 'like', "%{$request->product_name}%"));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('product_name', fn($row) => optional($row->product)->name ?? '-')
            ->addColumn('action', function ($row) {
                return view(
                    'erp.pages.adjustment-products.defect-products.partials.action-button',
                    compact('row')
                )->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function detailDefectProducts($id)
    {
        $product = Products::findOrFail($id);
        return view('erp.pages.adjustment-products.defect-products.detail-defect-products', compact('product'));
    }

    public function dataDetailDefectProducts(Request $request, $id)
    {
        $defectRecords = DefectProduct::with(['supplier', 'user'])
            ->where('product_id', $id)
            ->orderBy('defect_date', 'desc');

        return DataTables::of($defectRecords)
            ->addIndexColumn()
            ->addColumn('defect_date', fn($record) => $record->defect_date?->format('Y-m-d') ?? '-')
            ->addColumn('supplier', fn($record) => $record->supplier?->name ?? '-')
            ->addColumn('quantity', fn($record) => '<span class="text-danger fw-bold">' . number_format($record->quantity) . '</span>')
            ->addColumn('defect_type', fn($record) => ucfirst($record->defect_type ?? '-'))
            ->addColumn('note', fn($record) => $record->note ?? '-')
            ->addColumn('status', function ($record) {
                return match ($record->status) {
                    'pending'  => '<span class="badge bg-soft-warning text-warning">Pending</span>',
                    'returned' => '<span class="badge bg-soft-success text-success">Returned</span>',
                    'disposed' => '<span class="badge bg-soft-danger text-danger">Disposed</span>',
                    'repaired' => '<span class="badge bg-soft-info text-info">Repaired</span>',
                    default    => '<span class="badge bg-soft-secondary text-muted">' . ucfirst($record->status) . '</span>',
                };
            })
            ->addColumn('user', fn($record) => $record->user?->name ?? '-')
            ->addColumn('action', function ($record) {
                return view(
                    'erp.pages.adjustment-products.defect-products.partials.action-button-detail',
                    compact('record')
                )->render();
            })
            ->rawColumns(['quantity', 'status', 'action'])
            ->make(true);
    }
}
