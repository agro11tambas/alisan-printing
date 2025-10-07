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

    // public function dataOperators(Request $request)
    // {
    //     $query = Operator::query();

    //     if ($request->filled('name')) {
    //         $query->where('name', 'like', '%' . $request->name . '%');
    //     }

    //     return DataTables::of($query)
    //         ->addIndexColumn()
    //         ->addColumn('status', function ($row) {
    //             return $row->active
    //                 ? '<span class="badge bg-success">Active</span>'
    //                 : '<span class="badge bg-secondary">Inactive</span>';
    //         })
    //         ->addColumn('action', function ($row) {
    //             return view('erp.pages.operators.partials.action-button', compact('row'))->render();
    //         })
    //         ->rawColumns(['status', 'action'])
    //         ->make(true);
    // }

    public function dataOperators(Request $request)
    {
        $query = Operator::query()
            ->withSum('histories', 'change_quantity'); // ✅ ambil total dari tabel order_progress_histories_2

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
            ->addColumn('total_progress', function ($row) {
                // ini otomatis dari withSum()
                $total = $row->histories_sum_change_quantity ?? 0;
                return '<span class="fw-bold">' . number_format($total) . '</span>';
            })
            ->addColumn('action', function ($row) {
                return view('erp.pages.operators.partials.action-button', compact('row'))->render();
            })
            ->rawColumns(['status', 'total_progress', 'action'])
            ->make(true);
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
