<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\InventoryWarehouse;
use App\Models\ProductBundle;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Products;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\PurchaseProduct;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Models\ProductCombination;
use App\Models\ProductCombinationOption;
use App\Models\ProductionStock;
use App\Models\ProductionWarehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    public function index()
    {
        $products = Products::latest()->get();
        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        return view('erp.pages.products.index', compact('products', 'categories', 'tags'));
    }

    public function data(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        // ✅ Tambahkan inventoryStock ke eager loading biar gak N+1 query
        $products = Products::with(['categories', 'tags', 'inventoryStock'])
            ->orderBy('name', 'asc');

        // ✅ Filter berdasarkan nama atau SKU
        if ($request->filled('search_type') && $request->filled('search_keyword')) {
            $type = $request->search_type;
            $keyword = $request->search_keyword;

            $products->when(
                $type === 'name',
                fn($q) =>
                $q->where('name', 'like', "%{$keyword}%")
            )->when(
                $type === 'sku',
                fn($q) =>
                $q->where('sku', 'like', "%{$keyword}%")
            );
        }

        // ✅ Filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $categoryIds = (array) $request->category_id;
            $products->whereHas(
                'categories',
                fn($q) =>
                $q->whereIn('id', $categoryIds)
            );
        }

        // ✅ Filter berdasarkan tag
        if ($request->filled('tag_id')) {
            $tagIds = (array) $request->tag_id;
            $products->whereHas(
                'tags',
                fn($q) =>
                $q->whereIn('id', $tagIds)
            );
        }

        // ✅ Hindari query count dua kali
        $totalQuery = clone $products;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $products->skip($start)->take($length)->get();

        // ✅ Return format ringan dan cocok untuk lazy load (bukan DataTables draw)
        return response()->json([
            'data' => $data->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => e($product->name),
                    'categories' => $product->categories->map(
                        fn($c) => '<span class="badge bg-soft-primary text-primary">'
                            . e($c->name) . '</span>'
                    )->implode(' '),
                    'tags' => $product->tags->map(
                        fn($t) => '<span class="badge bg-soft-success text-success">'
                            . e($t->name) . '</span>'
                    )->implode(' '),
                    'price' => 'Rp ' . number_format($product->price, 0, ',', '.'),
                    'sku' => e($product->sku),
                    'avg_cost' => 'Rp ' . number_format(optional($product->inventoryStock)->avg_cost ?? 0, 2, ',', '.'),
                    'fixed_cost' => 'Rp ' . number_format($product->fixed_cost, 2, ',', '.'),
                    'action' => view('erp.pages.products.partials.action-button', compact('product'))->render(),
                ];
            }),
            'has_more' => $totalData > ($start + $length), // ✅ untuk infinite scroll
        ]);
    }

    public function create()
    {
        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        return view('erp.pages.products.create-product', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'price' => 'required',
            // 'stock' => 'required',
            // 'sale_price' => 'nullable',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if (Products::where('sku', $request->sku)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SKU sudah digunakan. Silakan gunakan SKU lain.');
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('uploads/products');

            // Pastikan foldernya ada
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $image->move($destinationPath, $filename);

            $imagePath = 'uploads/products/' . $filename;
        }

        $price = $request->filled('price') ? $request->price : 0;
        $fixedCost = $request->filled('fixed_cost') ? $request->fixed_cost : 0;

        $product = Products::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $price,
            // 'sale_price' => $request->sale_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            // 'stock' => $request->stock,
            'image' => $imagePath,
            'fixed_cost' => $fixedCost,
        ]);

        // Simpan relasi ke pivot table
        $product->categories()->sync($request->categories);
        $product->tags()->sync($request->tags);

        $productionWarehouse = ProductionWarehouse::firstOrCreate(
            ['name' => 'Gudang Produksi Utama'],
            ['location' => 'Kantor Pusat']
        );

        ProductionStock::create([
            'production_warehouse_id' => $productionWarehouse->id,
            'product_id' => $product->id,
            'available_quantity' => 0,
        ]);

        $inventoryWarehouse = InventoryWarehouse::firstOrCreate(
            ['name' => 'Gudang Inventory Utama'],
            ['location' => 'Kantor Pusat']
        );

        InventoryStock::create([
            'inventory_warehouse_id' => $inventoryWarehouse->id,
            'product_id' => $product->id,
            'opening_stock' => 0,
            'opening_rate' => 0,
            'inventory_stock' => 0,
            'incoming_stock' => 0,
            'stock_after_sales' => 0,
            'available_quantity' => 0,
        ]);

        return redirect('/erp/products')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function delete($id)
    {
        $product = Products::findOrFail($id);

        $bundles = ProductBundle::whereHas('items', function ($q) use ($id) {
            $q->where('product_id', $id);
        })->get();

        foreach ($bundles as $bundle) {
            $bundle->items()->delete();

            $bundle->delete();
        }

        $product->delete();

        return redirect('/erp/products')->with('success', 'Produk dan semua bundle yang terkait berhasil dihapus.');
    }

    public function edit($id)
    {
        $product = Products::with([
            'categories:id,name',
            'tags:id,name',
        ])->findOrFail($id);

        // Ambil semua kategori & tag untuk dropdown
        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        return view('erp.pages.products.edit-product', compact('product', 'categories', 'tags'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'price' => 'required',
            'stock' => 'required',
            'sale_price' => 'nullable',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'categories' => 'required|array',
            'tags' => 'required|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if (Products::where('sku', $request->sku)->where('id', '!=', $id)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SKU sudah digunakan oleh produk lain. Silakan gunakan SKU lain.');
        }

        $product = Products::findOrFail($id);
        $imagePath = $product->image;
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('uploads/products');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $image->move($destinationPath, $filename);

            $imagePath = 'uploads/products/' . $filename;
            $imageUrl = asset($imagePath);
        }

        $price = $request->filled('price') ? $request->price : 0;
        $fixedCost = $request->filled('fixed_cost') ? $request->fixed_cost : 0;

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $price,
            'sale_price' => $request->sale_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'stock' => $request->stock,
            'image' => $imagePath,
            'fixed_cost' => $fixedCost,
        ]);

        $product->categories()->sync($request->categories);
        $product->tags()->sync($request->tags);

        return redirect('/erp/products')->with('success', 'Produk berhasil diperbarui.');
    }
}
