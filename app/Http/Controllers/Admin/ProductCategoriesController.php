<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\ProductCategory;
use Yajra\DataTables\Facades\DataTables;

class ProductCategoriesController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::all();
        return view('erp.pages.product-categories.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $categories = ProductCategory::query();

        if ($request->filled('name')) {
            $categories->where('name', 'like', '%' . $request->name . '%');
        }

        $categories = $categories->orderBy('name', 'asc')->get();

        return DataTables::of($categories)
            ->addIndexColumn()
            ->addColumn('name', function ($category) {
                return $category->name;
            })
            ->addColumn('slug', function ($category) {
                return $category->slug;
            })
            ->addColumn('description', function ($category) {
                return $category->description;
            })
            ->addColumn('action', function ($category) {
                return view('erp.pages.product-categories.partials.action-button', compact('category'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.product-categories.create-category');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        ProductCategory::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect('/erp/products/categories')->with('success', 'Kategori berhasil ditambahkan.');
    }


    public function delete($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();
        return redirect()->back()->with('success', 'Kategori berhasil dihapus.');
    }

    public function edit($id)
    {
        $category = ProductCategory::findOrFail($id);
        return view('erp.pages.product-categories.edit-category', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect('/erp/products/categories')->with('success', 'Kategori berhasil diperbarui.');
    }
}
