<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\InventoryWarehouse;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\ProductBundleUnitConversion;
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
use App\Models\ProductUnit;
use App\Models\ProductUnitConversion;
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
        $products = Products::with([
            'categories',
            'tags',
            'inventoryStock',
            'unitConversions.unit',
        ])
            ->orderBy('name', 'asc');

        // ✅ Filter berdasarkan nama atau SKU
        if ($request->search_keyword) {
            $keyword = $request->search_keyword;
            $products->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            });
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
                    'product_units' => view(
                        'erp.pages.products.partials.product-units-table',
                        compact('product')
                    )->render(),
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
        $productUnits = ProductUnit::orderBy('name', 'asc')->get();

        return view('erp.pages.products.create-product', compact('categories', 'tags', 'productUnits'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required',
    //         'sku' => 'required|string',
    //         'price' => 'required|numeric|min:0',
    //         'fixed_cost' => 'nullable|numeric|min:0',
    //         'description' => 'nullable',
    //         'short_description' => 'nullable',
    //         'categories' => 'nullable|array',
    //         'tags' => 'nullable|array',
    //         'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

    //         'units' => 'nullable|array',
    //         'units.*.unit_id' => 'nullable|exists:product_units,id',
    //         'units.*.conversion_value' => 'nullable|numeric|min:0.01',
    //         'units.*.sale_price' => 'nullable|numeric|min:0',
    //     ]);

    //     if (Products::where('sku', $request->sku)->exists()) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'SKU sudah digunakan. Silakan gunakan SKU lain.');
    //     }

    //     try {
    //         DB::transaction(function () use ($request) {
    //             $imagePath = null;

    //             if ($request->hasFile('image')) {
    //                 $image = $request->file('image');
    //                 $filename = time() . '_' . $image->getClientOriginalName();
    //                 $destinationPath = public_path('uploads/products');

    //                 if (!file_exists($destinationPath)) {
    //                     mkdir($destinationPath, 0755, true);
    //                 }

    //                 $image->move($destinationPath, $filename);

    //                 $imagePath = 'uploads/products/' . $filename;
    //             }

    //             $price = $request->filled('price') ? (float) $request->price : 0;
    //             $fixedCost = $request->filled('fixed_cost') ? (float) $request->fixed_cost : 0;

    //             $product = Products::create([
    //                 'name' => $request->name,
    //                 'sku' => $request->sku,
    //                 'price' => $price,
    //                 'description' => $request->description,
    //                 'short_description' => $request->short_description,
    //                 'image' => $imagePath,
    //                 'fixed_cost' => $fixedCost,
    //                 'stock' => 0,
    //             ]);

    //             $product->categories()->sync($request->categories ?? []);
    //             $product->tags()->sync($request->tags ?? []);

    //             /*
    //         |--------------------------------------------------------------------------
    //         | Product Unit Conversion
    //         |--------------------------------------------------------------------------
    //         | Pcs otomatis dibuat sebagai unit dasar.
    //         | price produk = harga per pcs.
    //         | fixed_cost = cost per pcs.
    //         */
    //             $pcsUnit = ProductUnit::whereRaw('LOWER(name) = ?', ['pcs'])->first();

    //             if (!$pcsUnit) {
    //                 $pcsUnit = ProductUnit::create([
    //                     'name' => 'Pcs',
    //                     'description' => 'Default base unit',
    //                 ]);
    //             }

    //             ProductUnitConversion::create([
    //                 'product_id' => $product->id,
    //                 'unit_id' => $pcsUnit->id,
    //                 'conversion_value' => 1,
    //                 'sale_price' => $price,
    //                 'purchase_price' => $fixedCost,
    //             ]);

    //             foreach ($request->units ?? [] as $unit) {
    //                 $unitId = $unit['unit_id'] ?? null;
    //                 $conversionValue = $unit['conversion_value'] ?? null;
    //                 $salePrice = $unit['sale_price'] ?? null;

    //                 // Row kosong dari blade diabaikan.
    //                 if (empty($unitId) && empty($conversionValue) && empty($salePrice)) {
    //                     continue;
    //                 }

    //                 // Jangan simpan Pcs lagi, karena sudah otomatis dibuat di atas.
    //                 if ((int) $unitId === (int) $pcsUnit->id) {
    //                     continue;
    //                 }

    //                 ProductUnitConversion::create([
    //                     'product_id' => $product->id,
    //                     'unit_id' => $unitId,
    //                     'conversion_value' => $conversionValue,
    //                     'sale_price' => $salePrice ?? 0,
    //                     'purchase_price' => 0,
    //                 ]);
    //             }

    //             ProductionWarehouse::firstOrCreate(
    //                 ['id' => 2],
    //                 [
    //                     'name' => 'Gudang Produksi Utama',
    //                     'location' => 'Kantor Pusat',
    //                 ]
    //             );

    //             $productionWarehouseId = 2;

    //             ProductionStock::create([
    //                 'production_warehouse_id' => $productionWarehouseId,
    //                 'product_id' => $product->id,
    //                 'available_quantity' => 0,
    //             ]);

    //             $inventoryWarehouse = InventoryWarehouse::firstOrCreate(
    //                 ['name' => 'Gudang Inventory Utama'],
    //                 ['location' => 'Kantor Pusat']
    //             );

    //             InventoryStock::create([
    //                 'inventory_warehouse_id' => $inventoryWarehouse->id,
    //                 'product_id' => $product->id,
    //                 'opening_stock' => 0,
    //                 'opening_rate' => 0,
    //                 'inventory_stock' => 0,
    //                 'incoming_stock' => 0,
    //                 'stock_after_sales' => 0,
    //                 'available_quantity' => 0,
    //             ]);
    //         });

    //         return redirect('/erp/products')->with('success', 'Produk berhasil ditambahkan.');
    //     } catch (\Throwable $e) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Gagal menyimpan produk: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'base_unit_id' => 'required|exists:product_units,id',

            // price product tetap dikirim hidden = 0
            'price' => 'nullable|numeric|min:0',

            'description' => 'nullable',
            'short_description' => 'nullable',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'units' => 'nullable|array',
            'units.*.unit_id' => 'nullable|exists:product_units,id',
            'units.*.conversion_value' => 'nullable|numeric|min:0.01',
            'units.*.sale_price' => 'nullable|numeric|min:0',
            'units.*.fixed_cost' => 'nullable|numeric|min:0',
            'units.*.margin' => 'nullable|numeric|min:0',
        ]);

        if (Products::where('sku', $request->sku)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SKU sudah digunakan. Silakan gunakan SKU lain.');
        }

        try {
            DB::transaction(function () use ($request) {
                $imagePath = null;

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $destinationPath = public_path('uploads/products');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $image->move($destinationPath, $filename);
                    $imagePath = 'uploads/products/' . $filename;
                }

                $product = Products::create([
                    'name' => $request->name,
                    'sku' => $request->sku,
                    'base_unit_id' => $request->base_unit_id,

                    // price product dipaksa 0
                    'price' => 0,
                    'fixed_cost' => 0,

                    'description' => $request->description,
                    'short_description' => $request->short_description,
                    'image' => $imagePath,
                    'stock' => 0,
                ]);

                $product->categories()->sync($request->categories ?? []);
                $product->tags()->sync($request->tags ?? []);

                $savedUnitIds = [];

                // Ambil qty dari row base unit (PCS)
                $baseQty = 1;
                foreach ($request->units ?? [] as $unit) {
                    if (!empty($unit['unit_id']) && (int)$unit['unit_id'] === (int)$request->base_unit_id) {
                        $baseQty = (float)($unit['conversion_value'] ?? 1);
                        break;
                    }
                }

                foreach ($request->units ?? [] as $unit) {
                    $unitId = $unit['unit_id'] ?? null;
                    $conversionValue = $unit['conversion_value'] ?? null;
                    $salePrice = $unit['sale_price'] ?? 0;
                    $fixedCost = $unit['fixed_cost'] ?? 0;
                    $margin = $unit['margin'] ?? 0;

                    if (empty($unitId) && empty($conversionValue) && empty($salePrice) && empty($fixedCost) && empty($margin)) {
                        continue;
                    }

                    if (empty($unitId)) {
                        continue;
                    }

                    // if ((int) $unitId === (int) $request->base_unit_id) {
                    //     $conversionValue = 1;
                    // }

                    if ((int)$unitId === (int)$request->base_unit_id) {
                        $conversionValue = 1;
                    } else {
                        $qty = (float)($unit['conversion_value'] ?? 1);
                        $conversionValue = $qty > 0 ? $baseQty / $qty : 1;
                    }

                    if (in_array((int) $unitId, $savedUnitIds, true)) {
                        continue;
                    }

                    $savedUnitIds[] = (int) $unitId;

                    ProductUnitConversion::create([
                        'product_id' => $product->id,
                        'unit_id' => $unitId,
                        'conversion_value' => $conversionValue,
                        'ratio_value' => $unit['conversion_value'],
                        'sale_price' => $salePrice,
                        'purchase_price' => 0,
                        'fixed_cost' => $fixedCost,
                        'margin' => $margin,
                    ]);
                }

                // Pastikan base unit tetap tersimpan walaupun user tidak tambah row base unit
                if (!in_array((int) $request->base_unit_id, $savedUnitIds, true)) {
                    ProductUnitConversion::create([
                        'product_id' => $product->id,
                        'unit_id' => $request->base_unit_id,
                        'conversion_value' => 1,
                        'ratio_value' => 1,
                        'sale_price' => 0,
                        'purchase_price' => 0,
                        'fixed_cost' => 0,
                        'margin' => 0,
                    ]);
                }

                ProductionWarehouse::firstOrCreate(
                    ['id' => 2],
                    [
                        'name' => 'Gudang Produksi Utama',
                        'location' => 'Kantor Pusat',
                    ]
                );

                ProductionStock::create([
                    'production_warehouse_id' => 2,
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
            });

            return redirect('/erp/products')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan produk: ' . $e->getMessage());
        }
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

        $product->update(['sku' => null]);

        $product->delete();

        return redirect('/erp/products')->with('success', 'Produk dan semua bundle yang terkait berhasil dihapus.');
    }

    public function edit($id)
    {
        $product = Products::with([
            'categories:id,name',
            'tags:id,name',
            'unitConversions.unit',
        ])->findOrFail($id);

        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        $productUnits = ProductUnit::orderBy('name', 'asc')->get();

        $pcsUnit = ProductUnit::whereRaw('LOWER(name) = ?', ['pcs'])->first();

        return view('erp.pages.products.edit-product', compact(
            'product',
            'categories',
            'tags',
            'productUnits',
            'pcsUnit'
        ));
    }

    private function refreshBundlesByProduct($productId)
    {
        $bundleIds = ProductBundleItem::where('product_id', $productId)
            ->pluck('bundle_id')
            ->unique();

        foreach ($bundleIds as $bundleId) {
            $bundle = ProductBundle::with([
                'items.product.unitConversions',
                'items.product.baseUnit',
            ])->find($bundleId);

            if (!$bundle) {
                continue;
            }

            $primaryItem = $bundle->items->where('role', 'primary')->first();
            $secondaryItem = $bundle->items->where('role', 'secondary')->first();

            if (!$primaryItem || !$secondaryItem) {
                continue;
            }

            $primary = $primaryItem->product;
            $secondary = $secondaryItem->product;

            if (!$primary || !$secondary) {
                continue;
            }

            $baseUnitId = null;

            if (
                !empty($primary->base_unit_id) &&
                !empty($secondary->base_unit_id) &&
                (int) $primary->base_unit_id === (int) $secondary->base_unit_id
            ) {
                $baseUnitId = $primary->base_unit_id;
            }

            $primaryUnits = $primary->unitConversions->keyBy('unit_id');
            $secondaryUnits = $secondary->unitConversions->keyBy('unit_id');

            ProductBundleUnitConversion::where('product_bundle_id', $bundle->id)->delete();

            foreach ($primaryUnits as $unitId => $primaryUnit) {
                $secondaryUnit = $secondaryUnits->get($unitId);

                $primarySalePrice = (float) ($primaryUnit->sale_price ?? 0);
                $secondarySalePrice = $secondaryUnit ? (float) ($secondaryUnit->sale_price ?? 0) : 0;

                ProductBundleUnitConversion::create([
                    'product_bundle_id' => $bundle->id,
                    'unit_id' => $unitId,
                    'conversion_value' => $primaryUnit->conversion_value ?? 1,
                    'ratio_value' => $primaryUnit->ratio_value ?? $primaryUnit->conversion_value ?? 1,
                    'sale_price' => $primarySalePrice + $secondarySalePrice,
                ]);
            }

            foreach ($secondaryUnits as $unitId => $secondaryUnit) {
                if ($primaryUnits->has($unitId)) {
                    continue;
                }

                ProductBundleUnitConversion::create([
                    'product_bundle_id' => $bundle->id,
                    'unit_id' => $unitId,
                    'conversion_value' => $secondaryUnit->conversion_value ?? 1,
                    'ratio_value' => $secondaryUnit->ratio_value ?? $secondaryUnit->conversion_value ?? 1,
                    'sale_price' => $secondaryUnit->sale_price ?? 0,
                ]);
            }

            $bundlePrice = ProductBundleUnitConversion::where('product_bundle_id', $bundle->id)
                ->orderBy('id', 'asc')
                ->value('sale_price') ?? 0;

            $bundle->update([
                'price' => $bundlePrice,
                'base_unit_id' => $baseUnitId,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'base_unit_id' => 'required|exists:product_units,id',

            // Product price tetap hidden / default 0
            'price' => 'nullable|numeric|min:0',

            'sale_price' => 'nullable',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'categories' => 'required|array',
            'tags' => 'required|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'units' => 'nullable|array',
            'units.*.unit_id' => 'nullable|exists:product_units,id',
            'units.*.conversion_value' => 'nullable|numeric|min:0.01',
            'units.*.sale_price' => 'nullable|numeric|min:0',
            'units.*.fixed_cost' => 'nullable|numeric|min:0',
            'units.*.margin' => 'nullable|numeric|min:0',
        ]);

        if (Products::where('sku', $request->sku)->where('id', '!=', $id)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'SKU sudah digunakan oleh produk lain. Silakan gunakan SKU lain.');
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $product = Products::findOrFail($id);

                $imagePath = $product->image;

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $destinationPath = public_path('uploads/products');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $image->move($destinationPath, $filename);
                    $imagePath = 'uploads/products/' . $filename;
                }

                $product->update([
                    'name' => $request->name,
                    'sku' => $request->sku,
                    'base_unit_id' => $request->base_unit_id,

                    // product price/fixed_cost tidak dipakai lagi
                    'price' => 0,
                    'fixed_cost' => 0,

                    'sale_price' => $request->sale_price,
                    'description' => $request->description,
                    'short_description' => $request->short_description,
                    'image' => $imagePath,
                ]);

                $keepUnitIds = [];
                $savedUnitIds = [];

                $baseQty = 1;
                foreach ($request->units ?? [] as $unit) {
                    if (!empty($unit['unit_id']) && (int)$unit['unit_id'] === (int)$request->base_unit_id) {
                        $baseQty = (float)($unit['conversion_value'] ?? 1);
                        break;
                    }
                }

                foreach ($request->units ?? [] as $unit) {
                    $unitId = $unit['unit_id'] ?? null;
                    $conversionValue = $unit['conversion_value'] ?? null;
                    $salePrice = $unit['sale_price'] ?? 0;
                    $fixedCost = $unit['fixed_cost'] ?? 0;
                    $margin = $unit['margin'] ?? 0;

                    if (
                        empty($unitId) &&
                        empty($conversionValue) &&
                        empty($salePrice) &&
                        empty($fixedCost) &&
                        empty($margin)
                    ) {
                        continue;
                    }

                    if (empty($unitId)) {
                        continue;
                    }

                    // if ((int) $unitId === (int) $request->base_unit_id) {
                    //     $conversionValue = 1;
                    // }

                    if ((int)$unitId === (int)$request->base_unit_id) {
                        $conversionValue = 1;
                    } else {
                        $qty = (float)($unit['conversion_value'] ?? 1);
                        $conversionValue = $qty > 0 ? $baseQty / $qty : 1;
                    }

                    if (in_array((int) $unitId, $savedUnitIds, true)) {
                        continue;
                    }

                    $savedUnitIds[] = (int) $unitId;
                    $keepUnitIds[] = (int) $unitId;

                    ProductUnitConversion::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'unit_id' => $unitId,
                        ],
                        [
                            'conversion_value' => $conversionValue,
                            'ratio_value' => $unit['conversion_value'],
                            'sale_price' => $salePrice ?? 0,
                            'purchase_price' => 0,
                            'fixed_cost' => $fixedCost ?? 0,
                            'margin' => $margin ?? 0,
                        ]
                    );
                }

                // Pastikan base unit selalu ada
                if (!in_array((int) $request->base_unit_id, $keepUnitIds, true)) {
                    ProductUnitConversion::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'unit_id' => $request->base_unit_id,
                        ],
                        [
                            'conversion_value' => 1,
                            'ratio_value' => null,
                            'sale_price' => 0,
                            'purchase_price' => 0,
                            'fixed_cost' => 0,
                            'margin' => 0,
                        ]
                    );

                    $keepUnitIds[] = (int) $request->base_unit_id;
                }

                ProductUnitConversion::where('product_id', $product->id)
                    ->whereNotIn('unit_id', $keepUnitIds)
                    ->delete();

                // $bundleIds = \App\Models\ProductBundleItem::where('product_id', $id)
                //     ->pluck('bundle_id')
                //     ->unique();

                // foreach ($bundleIds as $bundleId) {
                //     $total = \App\Models\ProductBundleItem::where('bundle_id', $bundleId)
                //         ->join('products', 'product_bundle_items.product_id', '=', 'products.id')
                //         ->sum('products.price');

                //     \App\Models\ProductBundle::where('id', $bundleId)->update([
                //         'price' => $total,
                //     ]);
                // }

                $this->refreshBundlesByProduct($product->id);

                $product->categories()->sync($request->categories ?? []);
                $product->tags()->sync($request->tags ?? []);
            });

            return redirect('/erp/products')->with('success', 'Produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }
}
