<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceProductCategoryStoreRequest;
use App\Http\Requests\EcommerceProductCategoryUpdateRequest;
use App\Models\EcommerceProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class EcommerceProductCategoryController extends Controller
{
    public function index()
    {
        return view('erp.pages.ecommerce-product-categories.index');
    }

    public function data(Request $request)
    {
        $query = EcommerceProductCategory::query();

        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $query->orderBy('name');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('image', function ($category) {
                if (!$category->image) {
                    return '-';
                }

                $url = asset('storage/' . $category->image);

                return '<a href="' . $url . '" data-lightbox="category-' . $category->id . '">
                    <img src="' . $url . '" class="rounded" style="width:48px;height:48px;object-fit:cover;" alt="Category Image">
                </a>';
            })
            ->addColumn('name', fn($category) => e($category->name))
            ->addColumn('slug', fn($category) => e($category->slug))
            ->addColumn('description', fn($category) => e(Str::limit($category->description ?? '-', 80)))
            ->addColumn('action', function ($category) {
                return view('erp.pages.ecommerce-product-categories.partials.action-button', compact('category'))->render();
            })
            ->rawColumns(['image', 'action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.ecommerce-product-categories.create-category');
    }

    public function store(EcommerceProductCategoryStoreRequest $request)
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeFile($request->file('image'));
        $data['is_active'] = true;
        $data['sort_order'] = 0;

        EcommerceProductCategory::create($data);

        return redirect()
            ->route('erp.ecommerce-product-categories.index')
            ->with('success', 'Ecommerce Product Category berhasil dibuat.');
    }

    public function edit(EcommerceProductCategory $category)
    {
        return view('erp.pages.ecommerce-product-categories.edit-category', compact('category'));
    }

    public function update(EcommerceProductCategoryUpdateRequest $request, EcommerceProductCategory $category)
    {
        $data = $request->safe()->except('image');
        $data['image'] = $this->storeFile($request->file('image'), $category->image);
        $data['is_active'] = true;
        $data['sort_order'] = 0;

        $category->update($data);

        return redirect()
            ->route('erp.ecommerce-product-categories.index')
            ->with('success', 'Ecommerce Product Category berhasil diperbarui.');
    }

    public function destroy(EcommerceProductCategory $category)
    {
        $category->delete();

        return redirect()
            ->route('erp.ecommerce-product-categories.index')
            ->with('success', 'Ecommerce Product Category berhasil dihapus.');
    }

    public function restore($id)
    {
        $category = EcommerceProductCategory::onlyTrashed()->findOrFail($id);
        $category->restore();

        return redirect()
            ->route('erp.ecommerce-product-categories.index')
            ->with('success', 'Ecommerce Product Category berhasil direstore.');
    }

    private function storeFile($file, ?string $oldPath = null): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $file->store('ecommerce-products', 'public');
    }
}
