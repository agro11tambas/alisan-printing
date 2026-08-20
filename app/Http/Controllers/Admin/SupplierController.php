<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function index()
    {
        return view('erp.pages.suppliers.index');
    }

    public function data(Request $request)
    {
        $suppliers = Supplier::query();

        if ($request->filled('name')) {
            $suppliers->where('name', 'like', '%'.$request->name.'%');
        }

        return DataTables::of($suppliers)
            ->addIndexColumn()
            ->addColumn('name', function ($supplier) {
                return $supplier->name;
            })
            ->addColumn('phone', function ($supplier) {
                return '<strong>'.($supplier->phone ?? '-').'</strong>';
            })
            ->addColumn('action', function ($supplier) {
                return view('erp.pages.suppliers.partials.action-button', compact('supplier'))->render();
            })
            ->rawColumns(['action', 'phone'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.suppliers.create-supplier');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
        ]);

        Supplier::create([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect('/erp/suppliers')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('erp.pages.suppliers.edit-supplier', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect('/erp/suppliers')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function delete($id)
    {
        Supplier::findOrFail($id)->delete();

        return redirect('/erp/suppliers')->with('success', 'Supplier berhasil dihapus.');
    }
}
