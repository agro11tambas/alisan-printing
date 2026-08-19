<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    public function index()
    {
        return view('erp.pages.product-units.index');
    }

    public function data(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $productUnits = ProductUnit::query()
            ->orderBy('created_at', 'desc');

        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword;

            $productUnits->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        [$data, $hasMore] = $this->lazyLoadPage($productUnits, $start, $length);

        return response()->json([
            'data' => $data->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'name' => e($unit->name),
                    'description' => e($unit->description ?? '-'),
                    'action' => view('erp.pages.product-units.partials.action-button', [
                        'unit' => $unit,
                    ])->render(),
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    public function create()
    {
        return view('erp.pages.product-units.create-product-unit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:product_units,name',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama unit wajib diisi.',
            'name.max' => 'Nama unit maksimal 100 karakter.',
            'name.unique' => 'Nama unit sudah ada.',
            'description.string' => 'Description harus berupa teks.',
        ]);

        ProductUnit::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect('/erp/products/units')
            ->with('success', 'Product unit berhasil dibuat.');
    }

    public function edit($id)
    {
        $unit = ProductUnit::findOrFail($id);

        return view('erp.pages.product-units.edit-product-unit', compact('unit'));
    }

    public function update(Request $request, $id)
    {
        $unit = ProductUnit::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:product_units,name,' . $unit->id,
            'description' => 'nullable|string',
        ], [
            'name.required' => 'Nama unit wajib diisi.',
            'name.max' => 'Nama unit maksimal 100 karakter.',
            'name.unique' => 'Nama unit sudah ada.',
            'description.string' => 'Description harus berupa teks.',
        ]);

        $unit->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect('/erp/products/units')
            ->with('success', 'Product unit berhasil diupdate.');
    }

    public function delete($id)
    {
        $unit = ProductUnit::findOrFail($id);

        $unit->delete();

        return redirect('/erp/products/units')
            ->with('success', 'Product unit berhasil dihapus.');
    }
}
