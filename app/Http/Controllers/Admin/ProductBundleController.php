<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use Yajra\DataTables\Facades\DataTables;

class ProductBundleController extends Controller
{
    public function getProductBundles()
    {
        $bundles = ProductBundle::with('items.product')->get();
        return view('erp.pages.product-bundles.index', compact('bundles'));
    }

    public function dataProductBundles()
    {
        $bundles = ProductBundle::with('items.product');

        if (request()->filled('search_type') && request()->filled('search_keyword')) {
            $searchType = request()->search_type;
            $keyword = request()->search_keyword;

            if ($searchType === 'name') {
                $bundles->where('name', 'like', '%' . $keyword . '%');
            } elseif ($searchType === 'sku') {
                $bundles->where('sku', 'like', '%' . $keyword . '%');
            }
        }

        $bundles = $bundles->orderBy('name', 'asc')->get();

        return DataTables::of($bundles)
            ->addIndexColumn()
            ->addColumn('id', function ($bundle) {
                return $bundle->id;
            })
            ->addColumn('name', function ($bundle) {
                $productNames = $bundle->items->map(function ($item) {
                    return $item->product->name ?? '-';
                })->implode(' + ');

                return $productNames ?: '-';
            })
            ->addColumn('sku', function ($bundle) {
                return $bundle->sku;
            })
            ->addColumn('price', function ($bundle) {
                return 'Rp ' . number_format($bundle->price, 0);
            })
            ->addColumn('products', function ($bundle) {
                return $bundle->items->map(function ($item) {
                    return '<span class="badge bg-soft-primary text-primary">'
                        . $item->product->name .
                        ' (' . $item->quantity . ')</span>';
                })->implode(' ');
            })
            ->addColumn('action', function ($bundle) {
                return view('erp.pages.product-bundles.partials.action-button', compact('bundle'))->render();
            })
            ->rawColumns(['products', 'action'])
            ->toJson();
    }

    public function search(Request $request)
    {
        $search = $request->input('q', '');

        $products = Products::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->take(50)
            ->get()
            ->map(function ($product) {
                return [
                    'id'   => $product->id,
                    'text' => "{$product->name} - {$product->sku} (Rp" . number_format($product->price) . ")",
                    'name' => $product->name
                ];
            });

        return response()->json($products);
    }

    public function create()
    {
        $products = Products::all(); // ambil semua produk
        return view('erp.pages.product-bundles.create-product', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'sku'      => 'required|string|max:255|unique:product_bundles,sku',
            'price'    => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
        ]);

        // Simpan bundle
        $bundle = ProductBundle::create([
            'name'  => trim($request->name) . ' (BUNDLE)',
            'sku'   => $request->sku,
            'price' => $request->price,
        ]);

        // Simpan item bundle
        foreach ($request->products as $product_id) {
            ProductBundleItem::create([
                'bundle_id'  => $bundle->id,
                'product_id' => $product_id,
            ]);
        }

        return redirect('/erp/products/product-bundles')->with('success', 'Product Bundle berhasil dibuat!');
    }

    public function edit($id)
    {
        $bundle = ProductBundle::with('items')->findOrFail($id);
        $products = Products::all();

        // Ambil ID produk yang sudah ada di bundle untuk pre-select
        $selectedProducts = $bundle->items->pluck('product_id')->toArray();

        return view('erp.pages.product-bundles.edit-product', compact('bundle', 'products', 'selectedProducts'));
    }

    public function update(Request $request, $id)
    {
        $bundle = ProductBundle::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'sku'      => 'required|string|max:255|unique:product_bundles,sku,' . $bundle->id,
            'price'    => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
        ]);

        // Update data bundle
        $bundle->update([
            'name'  => $request->name,
            'sku'   => $request->sku,
            'price' => $request->price,
        ]);

        // Hapus semua item bundle lama
        ProductBundleItem::where('bundle_id', $bundle->id)->delete();

        // Simpan item bundle baru
        foreach ($request->products as $product_id) {
            ProductBundleItem::create([
                'bundle_id'  => $bundle->id,
                'product_id' => $product_id,
            ]);
        }

        return redirect('/erp/products/product-bundles')->with('success', 'Product Bundle berhasil diperbarui!');
    }

    public function delete($id)
    {
        $bundle = ProductBundle::findOrFail($id);
        $bundle->delete();
        return redirect('/erp/products/product-bundles')->with('success', 'Product Bundle berhasil dihapus!');
    }
}
