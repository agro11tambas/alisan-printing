<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OperatorController extends Controller
{
    public function getOperators()
    {
        $operators = Operator::latest()->get();
        return view('erp.pages.operators.operator', compact('operators'));
    }

    public function dataOperators(Request $request)
    {
        $query = Operator::query()
            ->withSum('histories as total_completed', 'completed_quantity')
            ->withSum('histories as total_defect', 'defect_quantity')
            ->withSum('histories as total_reject', 'reject_quantity');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                return $row->active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('completed', function ($row) {
                return '<span class="fw-bold text-success">' . number_format($row->total_completed ?? 0) . '</span>';
            })
            ->addColumn('defect_progress', function ($row) {
                return '<span class="fw-bold text-warning">' . number_format($row->total_defect ?? 0) . '</span>';
            })
            ->addColumn('reject_progress', function ($row) {
                return '<span class="fw-bold text-danger">' . number_format($row->total_reject ?? 0) . '</span>';
            })
            ->addColumn('total_progress', function ($row) {
                $total = ($row->total_completed ?? 0) + ($row->total_defect ?? 0) + ($row->total_reject ?? 0);
                return '<span class="fw-bold">' . number_format($total) . '</span>';
            })
            ->addColumn('action', function ($row) {
                return view('erp.pages.operators.partials.action-button', compact('row'))->render();
            })
            ->rawColumns(['status', 'completed', 'defect_progress', 'reject_progress', 'total_progress', 'action'])
            ->make(true);
    }

    // public function show($id)
    // {
    //     $operator = Operator::with([
    //         'histories.progressItem.product' => function ($q) {
    //             $q->withTrashed(); // kalau produk sudah dihapus
    //         },
    //     ])->findOrFail($id);

    //     // Gabungkan produk yang pernah di-progress
    //     $products = $operator->histories
    //         ->filter(fn($h) => $h->progressItem && $h->progressItem->product)
    //         ->groupBy('progressItem.product_id')
    //         ->map(function ($group) {
    //             $product = $group->first()->progressItem->product;
    //             $completed = $group->sum('completed_quantity');
    //             $defect = $group->sum('defect_quantity');
    //             $reject = $group->sum('reject_quantity');

    //             return [
    //                 'product_name' => $product->name ?? '-',
    //                 'sku' => $product->sku ?? '-',
    //                 'completed' => $completed,
    //                 'defect' => $defect,
    //                 'reject' => $reject,
    //             ];
    //         });

    //     return view('erp.pages.operators.detail', compact('operator', 'products'));
    // }

    public function show($id)
    {
        $operator = Operator::with([
            'histories.progressItem.product' => function ($q) {
                $q->withTrashed(); // kalau produk sudah dihapus
            },
        ])->findOrFail($id);

        // Gabungkan produk yang pernah di-progress
        $products = $operator->histories
            ->filter(fn($h) => $h->progressItem && $h->progressItem->product)
            ->groupBy('progressItem.product_id')
            ->map(function ($group) {
                $product = $group->first()->progressItem->product;
                $completed = $group->sum('completed_quantity');
                $defect = $group->sum('defect_quantity');
                $reject = $group->sum('reject_quantity');

                return [
                    'product_name' => $product->name ?? '-',
                    'sku' => $product->sku ?? '-',
                    'completed' => $completed,
                    'defect' => $defect,
                    'reject' => $reject,
                ];
            });

        // 🔹 Cek apakah permintaan JSON (lazy load API)
        if (request()->wantsJson()) {
            $length = (int) request()->input('length', 15);
            $start = (int) request()->input('start', 0);

            $total = $products->count();
            $paginated = $products->slice($start, $length)->values();

            return response()->json([
                'operator' => [
                    'id' => $operator->id,
                    'name' => $operator->name,
                    'role' => $operator->role,
                ],
                'data' => $paginated,
                'has_more' => $total > ($start + $length),
            ]);
        }

        // 🔹 View mode (aslinya tetap)
        return view('erp.pages.operators.detail', compact('operator', 'products'));
    }


    public function create()
    {
        return view('erp.pages.operators.create-operator');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        Operator::create($request->only(['name', 'active']));

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $operator = Operator::findOrFail($id);
        return view('erp.pages.operators.edit-operator', compact('operator'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        $operator = Operator::findOrFail($id);

        $operator->update($request->only(['name', 'active']));

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil diperbarui.');
    }

    public function delete($id)
    {
        $operator = Operator::findOrFail($id);

        $operator->delete();

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil dihapus.');
    }
}
