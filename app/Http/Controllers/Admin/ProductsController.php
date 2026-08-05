<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\InventoryWarehouse;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Services\ProductBundlePricingService;
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
use App\Models\ProductUnitPrice;
use App\Models\PriceMode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductsController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        return view('erp.pages.products.index', compact('categories', 'tags'));
    }

    public function data(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        // ✅ Tambahkan inventoryStock ke eager loading biar gak N+1 query
        $products = Products::with([
            'categories' => fn ($query) => $query->without('discounts'),
            'tags',
            'inventoryStock',
            'unitConversions.unit',
            'unitConversions.prices.priceMode',
            'baseUnit',
            'saleUnit',
            'purchaseUnit',
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
                    'base_unit_id' => $product->base_unit_id,
                    'base_unit_name' => e($product->baseUnit?->name ?? '-'),
                    'sale_unit_name' => e($product->saleUnit?->name ?? '-'),
                    'purchase_unit_name' => e($product->purchaseUnit?->name ?? '-'),
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
        $priceModes = PriceMode::active()->orderBy('sort_order')->orderBy('name')->get();

        return view('erp.pages.products.create-product', compact('categories', 'tags', 'productUnits', 'priceModes'));
    }

    public function store(Request $request)
    {
        $configuredUnitIds = collect($request->input('units', []))
            ->pluck('unit_id')
            ->push($request->input('base_unit_id'))
            ->filter()
            ->map(fn ($unitId) => (int) $unitId)
            ->unique()
            ->values()
            ->all();

        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'base_unit_id' => 'required|exists:product_units,id',
            'sale_unit_id' => ['required', 'exists:product_units,id', Rule::in($configuredUnitIds)],
            'purchase_unit_id' => ['required', 'exists:product_units,id', Rule::in($configuredUnitIds)],

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
            'prices' => 'nullable|array',
            'prices.*.price_mode_id' => ['required', Rule::exists('price_modes', 'id')->where('is_active', true)],
            'prices.*.unit_id' => 'required|exists:product_units,id',
            'prices.*.fixed_cost' => 'nullable|numeric|min:0',
            'prices.*.margin' => 'nullable|numeric|min:0',
            'prices.*.sale_price' => 'nullable|numeric|min:0',
        ], [
            'sale_unit_id.in' => 'Sale Unit harus dipilih dari Product Units.',
            'purchase_unit_id.in' => 'Purchase Unit harus dipilih dari Product Units.',
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
                    'sale_unit_id' => $request->sale_unit_id,
                    'purchase_unit_id' => $request->purchase_unit_id,

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

                $this->syncDynamicPrices($product, $request->input('prices', []));

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
        DB::transaction(function () use ($id) {
            $product = Products::findOrFail($id);

            $bundles = ProductBundle::withTrashed()
                ->whereHas('items', function ($q) use ($id) {
                    $q->withTrashed()->where('product_id', $id);
                })
                ->get();

            foreach ($bundles as $bundle) {
                $bundle->items()->delete();
                $bundle->update([
                    'sku' => $this->makeDeletedSku($bundle->sku),
                ]);
                if (!$bundle->trashed()) {
                    $bundle->delete();
                }
            }

            $product->update([
                'sku' => $this->makeDeletedSku($product->sku),
            ]);
            $product->delete();
        });

        return redirect('/erp/products')->with('success', 'Produk dan semua bundle yang terkait berhasil dihapus.');
    }

    private function makeDeletedSku(string $sku): string
    {
        $prefix = 'deleted-' . Str::random(10) . '-';

        return $prefix . Str::limit($sku, 255 - strlen($prefix), '');
    }

    public function edit($id)
    {
        $product = Products::with([
            'categories' => fn ($query) => $query
                ->select('product_categories.id', 'product_categories.name')
                ->without('discounts'),
            'tags:id,name',
            'unitConversions.unit',
            'unitConversions.prices.priceMode',
        ])->findOrFail($id);

        $categories = ProductCategory::all();
        $tags = ProductTag::all();

        $productUnits = ProductUnit::orderBy('name', 'asc')->get();
        $priceModes = PriceMode::orderBy('sort_order')->orderBy('name')->get();

        $pcsUnit = $productUnits->first(
            fn (ProductUnit $unit) => strtolower($unit->name) === 'pcs'
        );

        return view('erp.pages.products.edit-product', compact(
            'product',
            'categories',
            'tags',
            'productUnits',
            'pcsUnit',
            'priceModes'
        ));
    }

    private function refreshBundlesByProduct($productId)
    {
        $bundleIds = ProductBundleItem::where('product_id', $productId)
            ->pluck('bundle_id')
            ->unique()
            ->values();

        if ($bundleIds->isEmpty()) {
            return;
        }

        $service = app(ProductBundlePricingService::class);
        $activePriceModes = PriceMode::active()->get(['id', 'slug']);

        ProductBundle::whereIn('id', $bundleIds)
            ->with([
                'items.product.unitConversions.prices.priceMode',
                'items.product.baseUnit',
            ])
            ->get()
            ->each(
                fn (ProductBundle $bundle) => $service->sync($bundle, $activePriceModes)
            );
    }

    private function refreshBundlesAfterResponse(int $productId): void
    {
        app()->terminating(function () use ($productId) {
            try {
                $this->refreshBundlesByProduct($productId);
            } catch (\Throwable $e) {
                Log::error('Failed to refresh product bundles after product update.', [
                    'product_id' => $productId,
                    'exception' => $e,
                ]);
            }
        });
    }

    public function update(Request $request, $id)
    {
        $configuredUnitIds = collect($request->input('units', []))
            ->pluck('unit_id')
            ->push($request->input('base_unit_id'))
            ->filter()
            ->map(fn ($unitId) => (int) $unitId)
            ->unique()
            ->values()
            ->all();

        $request->validate([
            'name' => 'required',
            'sku' => 'required|string',
            'base_unit_id' => 'required|exists:product_units,id',
            'sale_unit_id' => ['required', 'exists:product_units,id', Rule::in($configuredUnitIds)],
            'purchase_unit_id' => ['required', 'exists:product_units,id', Rule::in($configuredUnitIds)],

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
            'prices' => 'nullable|array',
            'prices.*.price_mode_id' => ['required', 'exists:price_modes,id'],
            'prices.*.unit_id' => 'required|exists:product_units,id',
            'prices.*.fixed_cost' => 'nullable|numeric|min:0',
            'prices.*.margin' => 'nullable|numeric|min:0',
            'prices.*.sale_price' => 'nullable|numeric|min:0',
        ], [
            'sale_unit_id.in' => 'Sale Unit harus dipilih dari Product Units.',
            'purchase_unit_id.in' => 'Purchase Unit harus dipilih dari Product Units.',
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
                    'sale_unit_id' => $request->sale_unit_id,
                    'purchase_unit_id' => $request->purchase_unit_id,

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

                $this->syncDynamicPrices($product, $request->input('prices', []));
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

                $product->categories()->sync($request->categories ?? []);
                $product->tags()->sync($request->tags ?? []);
            });

            // Bundle prices are derived data. Recalculate after the response so
            // editing one product does not keep the product transaction locked.
            $this->refreshBundlesAfterResponse((int) $id);

            return redirect('/erp/products')->with('success', 'Produk berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    }
    private function syncDynamicPrices(Products $product, array $prices): void
    {
        $conversions = $product->unitConversions()->get()->keyBy(fn ($conversion) => (int) $conversion->unit_id);
        $priceModes = PriceMode::whereIn(
            'id',
            collect($prices)->pluck('price_mode_id')->filter()->unique()
        )->get()->keyBy(fn (PriceMode $mode) => (int) $mode->id);
        $keepPriceIds = [];
        $seen = [];

        foreach ($prices as $price) {
            $unitId = (int) ($price['unit_id'] ?? 0);
            $priceModeId = (int) ($price['price_mode_id'] ?? 0);
            $conversion = $conversions->get($unitId);
            $priceMode = $priceModes->get($priceModeId);

            if (!$conversion || !$priceMode) {
                continue;
            }

            $key = $conversion->id . ':' . $priceModeId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            // Fixed cost is owned by Product Units. Dynamic Price only controls the
            // margin and always derives its total from the selected unit.
            $fixedCost = (float) ($conversion->fixed_cost ?? 0);
            $margin = (float) ($price['margin'] ?? 0);
            $salePrice = $fixedCost + $margin;

            $dynamicPrice = ProductUnitPrice::updateOrCreate(
                [
                    'product_unit_conversion_id' => $conversion->id,
                    'price_mode_id' => $priceModeId,
                ],
                [
                    'fixed_cost' => $fixedCost,
                    'margin' => $margin,
                    'sale_price' => $salePrice,
                ]
            );

            $keepPriceIds[] = $dynamicPrice->id;

            // Keep legacy consumers working: the unit's default price is Polosan.
            if ($priceMode->slug === 'polosan') {
                $conversion->update([
                    'fixed_cost' => $fixedCost,
                    'margin' => $margin,
                    'sale_price' => $salePrice,
                ]);
            }
        }

        $query = ProductUnitPrice::whereIn('product_unit_conversion_id', $conversions->pluck('id'));
        if ($keepPriceIds) {
            $query->whereNotIn('id', $keepPriceIds);
        }
        $query->delete();
    }
}
