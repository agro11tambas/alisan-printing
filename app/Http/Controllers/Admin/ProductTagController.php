<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\ProductTag;
use Yajra\DataTables\Facades\DataTables;

class ProductTagController extends Controller
{
    public function index()
    {
        return view('erp.pages.product-tags.index');
    }

    public function data(Request $request)
    {
        $tags = ProductTag::query();

        if ($request->filled('name')) {
            $tags->where('name', 'like', '%' . $request->name . '%');
        }

        $tags = $tags->orderBy('name', 'asc');

        return DataTables::of($tags)
            ->addIndexColumn()
            ->addColumn('name', function ($tag) {
                return $tag->name;
            })
            ->addColumn('slug', function ($tag) {
                return $tag->slug;
            })
            ->addColumn('description', function ($tag) {
                return $tag->description;
            })
            ->addColumn('action', function ($tag) {
                return view('erp.pages.product-tags.partials.action-button', compact('tag'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.product-tags.create-tag');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        ProductTag::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect('/erp/products/tags')->with('success', 'Tag berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $tag = ProductTag::findOrFail($id);
        return view('erp.pages.product-tags.edit-tag', compact('tag'));
    }

    public function update(Request $request, $id)
    {
        $tag = ProductTag::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'slug' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $tag->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return redirect('/erp/products/tags')->with('success', 'Tag berhasil diperbarui.');
    }

    public function delete($id)
    {
        $tag = ProductTag::findOrFail($id);
        $tag->delete();
        
        return redirect()->back()->with('success', 'Tag berhasil dihapus.');
    }
}
