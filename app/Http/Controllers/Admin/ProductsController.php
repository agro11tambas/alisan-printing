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

    public function data()
    {
        $products = Products::with(['categories', 'tags']);

        if (request()->filled('search_type') && request()->filled('search_keyword')) {
            $searchType = request()->search_type;
            $keyword = request()->search_keyword;

            if ($searchType === 'name') {
                $products->where('name', 'like', '%' . $keyword . '%');
            } elseif ($searchType === 'sku') {
                $products->where('sku', 'like', '%' . $keyword . '%');
            }
        }

        if (request()->filled('category_id')) {
            $categoryIds = (array) request()->category_id; // selalu jadikan array

            $products->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('id', $categoryIds);
            });
        }

        if (request()->filled('tag_id')) {
            $tagIds = (array) request()->tag_id; // selalu jadikan array

            $products->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('id', $tagIds);
            });
        }

        $products = $products->orderBy('name', 'asc')->get();

        return DataTables::of($products)
            ->addIndexColumn()
            ->addColumn('id', function ($product) {
                return $product->id;
            })
            ->addColumn('image', function ($product) {
                $src = $product->image
                    ? asset($product->image)
                    : asset('uploads/products/default.png');

                return '
                    <a href="' . $src . '" data-lightbox="product-' . $product->id . '">
                        <img src="' . $src . '"
                            width="50"
                            height="50"
                            style="border-radius: 50%; object-fit: cover; object-position: center;"
                            alt="Image">
                    </a>
                ';
            })
            ->addColumn('name', function ($product) {
                return $product->name;
            })
            ->addColumn('categories', function ($product) {
                return $product->categories->map(function ($category) {
                    return '<span class="badge bg-soft-primary text-primary">' . $category->name . '</span>';
                })->implode(' ');
            })
            ->addColumn('tags', function ($product) {
                return $product->tags->map(function ($tag) {
                    return '<span class="badge bg-soft-success text-success">' . $tag->name . '</span>';
                })->implode(' ');
            })
            ->addColumn('avg_cost', function ($product) {
                return 'Rp ' . number_format($product->inventoryStock->avg_cost, 2, ',', '.');
            })
            ->addColumn('price', function ($product) {
                return 'Rp ' . number_format($product['price'], 0, ',', '.');
            })
            ->addColumn('sku', function ($product) {
                return $product->sku;
            })
            ->addColumn('action', function ($product) {
                return view('erp.pages.products.partials.action-button', compact('product'))->render();
            })
            ->rawColumns(['image', 'categories', 'tags', 'stock', 'action'])
            ->toJson();
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

        $product = Products::create([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price,
            // 'sale_price' => $request->sale_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            // 'stock' => $request->stock,
            'image' => $imagePath,
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

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'stock' => $request->stock,
            'image' => $imagePath,
        ]);

        $product->categories()->sync($request->categories);
        $product->tags()->sync($request->tags);

        return redirect('/erp/products')->with('success', 'Produk berhasil diperbarui.');
    }
}
