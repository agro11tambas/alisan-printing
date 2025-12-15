<?php

namespace App\Http\Controllers\Admin;

use App\Models\Products;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class ProductBundleController extends Controller
{
    public function getProductBundles()
    {
        $bundles = ProductBundle::with('items.product')->get();
        return view('erp.pages.product-bundles.index', compact('bundles'));
    }

    public function dataProductBundles(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $bundles = ProductBundle::with('items.product');

        // 🔍 Filter pencarian
        if ($request->filled('search_type') && $request->filled('search_keyword')) {
            $searchType = $request->search_type;
            $keyword = $request->search_keyword;

            if ($searchType === 'name') {
                $bundles->where('name', 'like', '%' . $keyword . '%');
            } elseif ($searchType === 'sku') {
                $bundles->where('sku', 'like', '%' . $keyword . '%');
            }
        }

        $bundles->orderBy('name', 'asc');

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $bundles;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $bundles->skip($start)->take($length)->get();

        // 🔹 Return JSON versi lazy-load
        return response()->json([
            'data' => $data->values()->map(function ($bundle, $index) use ($start) {
                $productNames = $bundle->items->map(function ($item) {
                    return $item->product->name ?? '-';
                })->implode(' + ');

                $productBadges = $bundle->items->map(function ($item) {
                    return '<span class="badge bg-soft-primary text-primary">'
                        . e($item->product->name)
                        . ' (' . e($item->quantity) . ')</span>';
                })->implode(' ');

                return [
                    'DT_RowIndex' => $start + $index + 1, // ✅ FIXED
                    'id' => $bundle->id,
                    'name' => $productNames ?: '-',
                    'sku' => e($bundle->sku),
                    'price' => 'Rp ' . number_format($bundle->price, 0, ',', '.'),
                    'products' => $productBadges,
                    'action' => view('erp.pages.product-bundles.partials.action-button', compact('bundle'))->render(),
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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

        // 🧩 Cek SKU di tabel product_bundles dan products
        $skuExists = \App\Models\ProductBundle::where('sku', $request->sku)->exists()
            || \App\Models\Products::where('sku', $request->sku)->exists();

        if ($skuExists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Product Bundle dengan SKU yang sama sudah ada di ERP dengan nama ' . ProductBundle::where('sku', $request->sku)->first()->name . '. Silakan gunakan SKU lain.');
        }

        // Simpan bundle
        $bundle = \App\Models\ProductBundle::create([
            'name'  => trim($request->name) . ' (BUNDLE)',
            'sku'   => $request->sku,
            'price' => $request->price,
        ]);

        // Simpan item bundle
        foreach ($request->products as $product_id) {
            \App\Models\ProductBundleItem::create([
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
            'sku'      => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
        ]);

        // 🧩 Cek SKU duplikat di product_bundles (kecuali bundle ini) dan di products
        $skuExists = ProductBundle::where('sku', $request->sku)
            ->where('id', '!=', $bundle->id)
            ->exists()
            || Products::where('sku', $request->sku)->exists();

        if ($skuExists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Product Bundle dengan SKU yang sama sudah ada di ERP dengan nama ' . ProductBundle::where('sku', $request->sku)->first()->name . '. Silakan gunakan SKU lain.');
        }

        // 🔄 Update data utama bundle
        $bundle->update([
            'name'  => trim($request->name),
            'sku'   => $request->sku,
            'price' => $request->price,
        ]);

        // 🔁 Hapus item lama & simpan ulang produk baru
        ProductBundleItem::where('bundle_id', $bundle->id)->delete();

        foreach ($request->products as $product_id) {
            ProductBundleItem::create([
                'bundle_id'  => $bundle->id,
                'product_id' => $product_id,
            ]);
        }

        return redirect('/erp/products/product-bundles')
            ->with('success', 'Product Bundle berhasil diperbarui!');
    }

    // public function delete($id)
    // {
    //     $bundle = ProductBundle::findOrFail($id);
    //     $bundle->delete();
    //     return redirect('/erp/products/product-bundles')->with('success', 'Product Bundle berhasil dihapus!');
    // }

    public function delete($id)
    {
        $bundle = ProductBundle::findOrFail($id);

        $new_sku = 'deleted-' . Str::random(10) . '-' . $bundle->sku;

        if (strlen($new_sku) > 255) {
            $new_sku = 'deleted-' . Str::random(10) . '-' . substr($bundle->sku, -200);
        }

        $bundle->sku = $new_sku;
        $bundle->save();

        $bundle->delete();

        return redirect('/erp/products/product-bundles')->with('success', 'Product Bundle berhasil dihapus!');
    }
}
