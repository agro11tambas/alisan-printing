<?php

namespace App\Http\Controllers\Admin;

use App\Models\Products;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Http\Controllers\Controller;
use App\Models\ProductBundleUnitConversion;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;
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

        $bundles = ProductBundle::with([
            'items.product',
            'unitConversions.unit',
        ]);

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
                    'bundle_units' => view(
                        'erp.pages.product-bundles.partials.bundle-units-table',
                        compact('bundle')
                    )->render(),
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
        $products = Products::orderBy('name', 'asc')->get();
        $productUnits = ProductUnit::orderBy('name', 'asc')->get();

        return view('erp.pages.product-bundles.create-product', compact(
            'products',
            'productUnits'
        ));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name'     => 'required|string|max:255',
    //         'sku'      => 'required|string|max:255',
    //         'products' => 'required|array|min:2',
    //         'products.*' => 'required|exists:products,id',
    //         'base_unit_id' => 'required|exists:product_units,id',

    //         'units' => 'nullable|array',
    //         'units.*.unit_id' => 'nullable|exists:product_units,id',
    //         'units.*.conversion_value' => 'nullable|numeric|min:0.01',
    //     ], [
    //         'products.min' => 'Minimal pilih 2 produk untuk membuat bundle.',
    //         'units.required' => 'Minimal satu product unit wajib diisi.',
    //         'units.*.unit_id.required' => 'Unit wajib dipilih.',
    //         'units.*.conversion_value.required' => 'Conversion wajib diisi.',
    //         'units.*.conversion_value.min' => 'Conversion wajib lebih dari 0.',
    //     ]);

    //     $bundleSku = ProductBundle::where('sku', $request->sku)->first();
    //     $productSku = Products::where('sku', $request->sku)->first();

    //     if ($bundleSku || $productSku) {
    //         $existingName = $bundleSku?->name ?? $productSku?->name ?? '-';

    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'SKU sudah digunakan di ERP dengan nama ' . $existingName . '. Silakan gunakan SKU lain.');
    //     }

    //     $selectedProducts = collect($request->products)
    //         ->filter()
    //         ->unique()
    //         ->values();

    //     if ($selectedProducts->count() < 2) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Minimal pilih 2 produk berbeda untuk membuat bundle.');
    //     }

    //     $selectedUnits = collect($request->units)
    //         ->pluck('unit_id')
    //         ->filter()
    //         ->values();

    //     if ($selectedUnits->count() !== $selectedUnits->unique()->count()) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Unit tidak boleh duplikat.');
    //     }

    //     try {
    //         DB::transaction(function () use ($request, $selectedProducts) {
    //             $bundle = ProductBundle::create([
    //                 'name'  => trim($request->name) . ' (BUNDLE)',
    //                 'sku'   => $request->sku,
    //                 'price' => 0,
    //                 'base_unit_id' => $request->base_unit_id,
    //             ]);

    //             foreach ($selectedProducts as $productId) {
    //                 ProductBundleItem::create([
    //                     'bundle_id'  => $bundle->id,
    //                     'product_id' => $productId,
    //                     'quantity'   => 1,
    //                 ]);
    //             }

    //             $pcsUnit = ProductUnit::whereRaw('LOWER(name) = ?', ['pcs'])->first();

    //             if (!$pcsUnit) {
    //                 $pcsUnit = ProductUnit::create([
    //                     'name' => 'Pcs',
    //                     'description' => 'Default base unit',
    //                 ]);
    //             }

    //             ProductBundleUnitConversion::create([
    //                 'product_bundle_id' => $bundle->id,
    //                 'unit_id' => $pcsUnit->id,
    //                 'conversion_value' => 1,
    //                 'sale_price' => 0,
    //             ]);

    //             $baseQty = 1;
    //             $pcsSalePrice = 0;

    //             foreach ($request->units ?? [] as $unit) {
    //                 if (!empty($unit['unit_id']) && (int) $unit['unit_id'] === (int) $pcsUnit->id) {
    //                     $baseQty = (float) ($unit['conversion_value'] ?? 1);
    //                     $pcsSalePrice = $unit['sale_price'] ?? 0;
    //                     break;
    //                 }
    //             }

    //             ProductBundleUnitConversion::create([
    //                 'product_bundle_id' => $bundle->id,
    //                 'unit_id' => $pcsUnit->id,
    //                 'conversion_value' => 1,
    //                 'ratio_value' => $baseQty,
    //                 'sale_price' => $pcsSalePrice,
    //             ]);

    //             foreach ($request->units ?? [] as $unit) {
    //                 $unitId = $unit['unit_id'] ?? null;
    //                 $conversionInput = $unit['conversion_value'] ?? null;
    //                 $salePrice = $unit['sale_price'] ?? null;

    //                 if (empty($unitId) && empty($conversionInput) && empty($salePrice)) {
    //                     continue;
    //                 }

    //                 if ((int) $unitId === (int) $pcsUnit->id) {
    //                     continue;
    //                 }

    //                 $qty = (float) ($conversionInput ?? 1);
    //                 $conversionValue = $qty > 0 ? $baseQty / $qty : 1;

    //                 ProductBundleUnitConversion::create([
    //                     'product_bundle_id' => $bundle->id,
    //                     'unit_id' => $unitId,
    //                     'conversion_value' => $conversionValue,
    //                     'ratio_value' => $conversionInput,
    //                     'sale_price' => $salePrice ?? 0,
    //                 ]);
    //             }
    //         });

    //         return redirect('/erp/products/product-bundles')
    //             ->with('success', 'Product Bundle berhasil dibuat!');
    //     } catch (\Throwable $e) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Gagal membuat Product Bundle: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'primary_product_id' => 'required|exists:products,id',
            'secondary_product_ids' => 'required|array|min:1',
            'secondary_product_ids.*' => 'required|exists:products,id|different:primary_product_id',
        ], [
            'primary_product_id.required' => 'Primary product wajib dipilih.',
            'secondary_product_ids.required' => 'Minimal pilih 1 secondary product.',
            'secondary_product_ids.min' => 'Minimal pilih 1 secondary product.',
            'secondary_product_ids.*.different' => 'Secondary tidak boleh sama dengan primary.',
        ]);

        $secondaryIds = collect($request->secondary_product_ids)
            ->filter()
            ->unique()
            ->values();

        try {
            DB::transaction(function () use ($request, $secondaryIds) {
                $primary = Products::with(['unitConversions', 'baseUnit'])
                    ->findOrFail($request->primary_product_id);

                foreach ($secondaryIds as $secondaryId) {
                    $secondary = Products::with(['unitConversions', 'baseUnit'])
                        ->findOrFail($secondaryId);

                    $bundleName = trim($primary->name . ' + ' . $secondary->name) . ' (BUNDLE)';
                    $bundleSku = trim($primary->sku . $secondary->sku);

                    $existsBundleSku = ProductBundle::where('sku', $bundleSku)->first();
                    $existsProductSku = Products::where('sku', $bundleSku)->first();

                    if ($existsBundleSku || $existsProductSku) {
                        $existingName = $existsBundleSku?->name ?? $existsProductSku?->name ?? '-';

                        throw new \Exception('SKU ' . $bundleSku . ' sudah digunakan dengan nama ' . $existingName);
                    }

                    $baseUnitId = null;

                    if (
                        !empty($primary->base_unit_id) &&
                        !empty($secondary->base_unit_id) &&
                        (int) $primary->base_unit_id === (int) $secondary->base_unit_id
                    ) {
                        $baseUnitId = $primary->base_unit_id;
                    }

                    $bundle = ProductBundle::create([
                        'name' => $bundleName,
                        'sku' => $bundleSku,
                        'price' => 0,
                        'base_unit_id' => $baseUnitId,
                    ]);

                    ProductBundleItem::create([
                        'bundle_id' => $bundle->id,
                        'product_id' => $primary->id,
                        'quantity' => 1,
                    ]);

                    ProductBundleItem::create([
                        'bundle_id' => $bundle->id,
                        'product_id' => $secondary->id,
                        'quantity' => 1,
                    ]);

                    $primaryUnits = $primary->unitConversions->keyBy('unit_id');
                    $secondaryUnits = $secondary->unitConversions->keyBy('unit_id');

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
                }
            });

            return redirect('/erp/products/product-bundles')
                ->with('success', 'Product Bundle berhasil dibuat!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat Product Bundle: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $bundle = ProductBundle::with([
            'items',
            'unitConversions.unit',
        ])->findOrFail($id);

        $products = Products::orderBy('name', 'asc')->get();
        $productUnits = ProductUnit::orderBy('name', 'asc')->get();

        $selectedProducts = $bundle->items->pluck('product_id')->toArray();

        return view('erp.pages.product-bundles.edit-product', compact(
            'bundle',
            'products',
            'productUnits',
            'selectedProducts'
        ));
    }

    // public function update(Request $request, $id)
    // {
    //     $bundle = ProductBundle::findOrFail($id);

    //     $request->validate([
    //         'name'     => 'required|string|max:255',
    //         'sku'      => 'required|string|max:255',
    //         'products' => 'required|array|min:2',
    //         'products.*' => 'required|exists:products,id',

    //         'units' => 'nullable|array',
    //         'units.*.unit_id' => 'nullable|exists:product_units,id',
    //         'units.*.conversion_value' => 'nullable|numeric|min:0.01',
    //         'units.*.sale_price' => 'nullable|numeric|min:0',
    //     ], [
    //         'products.min' => 'Minimal pilih 2 produk untuk membuat bundle.',
    //         'units.*.conversion_value.min' => 'Conversion wajib lebih dari 0.',
    //     ]);

    //     $bundleSku = ProductBundle::where('sku', $request->sku)
    //         ->where('id', '!=', $bundle->id)
    //         ->first();

    //     $productSku = Products::where('sku', $request->sku)->first();

    //     if ($bundleSku || $productSku) {
    //         $existingName = $bundleSku?->name ?? $productSku?->name ?? '-';

    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'SKU sudah digunakan di ERP dengan nama ' . $existingName . '. Silakan gunakan SKU lain.');
    //     }

    //     $selectedProducts = collect($request->products)
    //         ->filter()
    //         ->unique()
    //         ->values();

    //     if ($selectedProducts->count() < 2) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Minimal pilih 2 produk berbeda untuk membuat bundle.');
    //     }

    //     $selectedUnits = collect($request->units ?? [])
    //         ->pluck('unit_id')
    //         ->filter()
    //         ->values();

    //     if ($selectedUnits->count() !== $selectedUnits->unique()->count()) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Unit tidak boleh duplikat.');
    //     }

    //     try {
    //         DB::transaction(function () use ($request, $bundle, $selectedProducts) {
    //             $bundle->update([
    //                 'name'  => trim($request->name),
    //                 'sku'   => $request->sku,
    //                 'price' => 0,
    //                 'base_unit_id' => $request->base_unit_id,
    //             ]);

    //             ProductBundleItem::where('bundle_id', $bundle->id)->delete();

    //             foreach ($selectedProducts as $productId) {
    //                 ProductBundleItem::create([
    //                     'bundle_id'  => $bundle->id,
    //                     'product_id' => $productId,
    //                     'quantity'   => 1,
    //                 ]);
    //             }

    //             $pcsUnit = ProductUnit::whereRaw('LOWER(name) = ?', ['pcs'])->first();

    //             if (!$pcsUnit) {
    //                 $pcsUnit = ProductUnit::create([
    //                     'name' => 'Pcs',
    //                     'description' => 'Default base unit',
    //                 ]);
    //             }

    //             ProductBundleUnitConversion::where('product_bundle_id', $bundle->id)->delete();

    //             $baseQty = 1;
    //             $pcsSalePrice = 0;

    //             foreach ($request->units ?? [] as $unit) {
    //                 if (!empty($unit['unit_id']) && (int) $unit['unit_id'] === (int) $pcsUnit->id) {
    //                     $baseQty = (float)($unit['conversion_value'] ?? 1);
    //                     $pcsSalePrice = $unit['sale_price'] ?? 0;
    //                     break;
    //                 }
    //             }

    //             ProductBundleUnitConversion::create([
    //                 'product_bundle_id' => $bundle->id,
    //                 'unit_id' => $pcsUnit->id,
    //                 'conversion_value' => 1,
    //                 'ratio_value' => $baseQty,
    //                 'sale_price' => $pcsSalePrice,
    //             ]);

    //             ProductBundleUnitConversion::where('product_bundle_id', $bundle->id)
    //                 ->where('unit_id', $pcsUnit->id)
    //                 ->update(['ratio_value' => $baseQty]);

    //             foreach ($request->units ?? [] as $unit) {
    //                 $unitId = $unit['unit_id'] ?? null;
    //                 $conversionValue = $unit['conversion_value'] ?? null;
    //                 $salePrice = $unit['sale_price'] ?? null;

    //                 // Row kosong dari blade diabaikan.
    //                 if (empty($unitId) && empty($conversionValue) && empty($salePrice)) {
    //                     continue;
    //                 }

    //                 // Jangan simpan PCS lagi, karena sudah otomatis dibuat di atas.
    //                 if ((int) $unitId === (int) $pcsUnit->id) {
    //                     continue;
    //                 }

    //                 $qty = (float)($unit['conversion_value'] ?? 1);
    //                 $conversionValue = $qty > 0 ? $baseQty / $qty : 1;

    //                 ProductBundleUnitConversion::create([
    //                     'product_bundle_id' => $bundle->id,
    //                     'unit_id' => $unitId,
    //                     'conversion_value' => $conversionValue,
    //                     'ratio_value' => $unit['conversion_value'],
    //                     'sale_price' => $salePrice ?? 0,
    //                 ]);
    //             }
    //         });

    //         return redirect('/erp/products/product-bundles')
    //             ->with('success', 'Product Bundle berhasil diperbarui!');
    //     } catch (\Throwable $e) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', 'Gagal memperbarui Product Bundle: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $bundle = ProductBundle::findOrFail($id);

        $request->validate([
            'primary_product_id' => 'required|exists:products,id',
            'secondary_product_ids' => 'required|array|min:1',
            'secondary_product_ids.*' => 'required|exists:products,id|different:primary_product_id',
        ], [
            'primary_product_id.required' => 'Primary product wajib dipilih.',
            'secondary_product_ids.required' => 'Minimal pilih 1 secondary product.',
            'secondary_product_ids.min' => 'Minimal pilih 1 secondary product.',
            'secondary_product_ids.*.different' => 'Secondary tidak boleh sama dengan primary.',
        ]);

        $secondaryIds = collect($request->secondary_product_ids)
            ->filter()
            ->unique()
            ->values();

        if ($secondaryIds->count() < 1) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Minimal pilih 1 secondary product.');
        }

        try {
            DB::transaction(function () use ($request, $bundle, $secondaryIds) {
                $primary = Products::with(['unitConversions', 'baseUnit'])
                    ->findOrFail($request->primary_product_id);

                $secondary = Products::with(['unitConversions', 'baseUnit'])
                    ->findOrFail($secondaryIds->first());

                $bundleName = trim($primary->name . ' + ' . $secondary->name) . ' (BUNDLE)';
                $bundleSku = trim($primary->sku . $secondary->sku);

                $existsBundleSku = ProductBundle::where('sku', $bundleSku)
                    ->where('id', '!=', $bundle->id)
                    ->first();

                $existsProductSku = Products::where('sku', $bundleSku)->first();

                if ($existsBundleSku || $existsProductSku) {
                    $existingName = $existsBundleSku?->name ?? $existsProductSku?->name ?? '-';

                    throw new \Exception('SKU ' . $bundleSku . ' sudah digunakan dengan nama ' . $existingName);
                }

                $baseUnitId = null;

                if (
                    !empty($primary->base_unit_id) &&
                    !empty($secondary->base_unit_id) &&
                    (int) $primary->base_unit_id === (int) $secondary->base_unit_id
                ) {
                    $baseUnitId = $primary->base_unit_id;
                }

                $bundle->update([
                    'name' => $bundleName,
                    'sku' => $bundleSku,
                    'price' => 0,
                    'base_unit_id' => $baseUnitId,
                ]);

                ProductBundleItem::where('bundle_id', $bundle->id)->delete();

                ProductBundleItem::create([
                    'bundle_id' => $bundle->id,
                    'product_id' => $primary->id,
                    'quantity' => 1,
                ]);

                ProductBundleItem::create([
                    'bundle_id' => $bundle->id,
                    'product_id' => $secondary->id,
                    'quantity' => 1,
                ]);

                ProductBundleUnitConversion::where('product_bundle_id', $bundle->id)->delete();

                $primaryUnits = $primary->unitConversions->keyBy('unit_id');
                $secondaryUnits = $secondary->unitConversions->keyBy('unit_id');

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
            });

            return redirect('/erp/products/product-bundles')
                ->with('success', 'Product Bundle berhasil diperbarui!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui Product Bundle: ' . $e->getMessage());
        }
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
