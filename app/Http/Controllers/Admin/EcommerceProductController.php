<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceProductStoreRequest;
use App\Http\Requests\EcommerceProductUpdateRequest;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductImage;
use App\Models\EcommerceProductCategory;
use App\Models\EcommerceVariantOption;
use App\Models\EcommerceVariantCombination;
use App\Models\Products;
use App\Models\ProductBundle;
use App\Models\ProductUnit;
use App\Services\EcommercePricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EcommerceProductController extends Controller
{
    public function __construct(private EcommercePricingService $pricingService)
    {
    }

    public function index()
    {
        $categories = EcommerceProductCategory::orderBy('name')->get();

        return view('erp.pages.ecommerce-products.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $query = EcommerceProduct::with(['categories', 'unit']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('ecommerce_product_categories.id', $request->category_id);
            });
        }

        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%");
            });
        }

        $query->orderBy('title');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('image', function ($product) {
                if (!$product->main_image) {
                    return '-';
                }

                $url = asset('uploads/' . $product->main_image);

                return '<a href="' . $url . '" data-lightbox="product-' . $product->id . '">
                    <img src="' . $url . '" class="rounded" style="width:48px;height:48px;object-fit:cover;" alt="Product Image">
                </a>';
            })
            ->addColumn('title', fn($product) => $product->title)
            ->addColumn('status', function ($product) {
                if ($product->is_active) {
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('category', function ($product) {
                if ($product->categories->isEmpty()) return '-';
                return $product->categories->pluck('name')->implode(', ');
            })
            ->addColumn('unit', fn($product) => $product->unit?->name ?? '-')
            ->addColumn('created_at', fn($product) => optional($product->created_at)->format('d M Y H:i'))
            ->addColumn('action', function ($product) {
                return view('erp.pages.ecommerce-products.partials.action-button', compact('product'))->render();
            })
            ->rawColumns(['image', 'status', 'action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.ecommerce-products.create-product', $this->formData());
    }

    public function getBundleSecondaryProducts(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        $unitId = $request->input('unit_id');
        $allowedModeIds = array_map('intval', $request->input('price_mode_ids', []));

        if (empty($productIds)) {
            return response()->json(['secondaries' => [], 'pairs' => []]);
        }

        // Get bundles where ANY of the given product IDs is the primary item
        $bundles = ProductBundle::whereHas('items', function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)
                  ->where('role', 'primary');
        })->with(['items.product', 'unitConversions.prices.priceMode'])->get();

        $secondaryProducts = collect();
        $pairs = [];

        foreach ($bundles as $bundle) {
            $primaryItem = $bundle->items->firstWhere('role', 'primary');
            $secondaryItem = $bundle->items->firstWhere('role', 'secondary');

            if (!$primaryItem?->product || !$secondaryItem?->product) {
                continue;
            }

            // Collect unique secondaries
            $secondaryProducts->push([
                'id' => $secondaryItem->product->id,
                'name' => $secondaryItem->product->name,
                'sku' => $secondaryItem->product->sku,
            ]);

            $modePrices = $unitId
                ? $this->pricingService->bundleModePrices($bundle, (int) $unitId)
                : [];
            if (!empty($allowedModeIds)) {
                $modePrices = array_values(array_filter(
                    $modePrices,
                    fn ($modePrice) => in_array($modePrice['price_mode_id'], $allowedModeIds, true)
                ));
            }
            $defaultPrice = $this->pricingService->defaultPrice($modePrices);

            $pairs[] = [
                'primary_product_id' => $primaryItem->product_id,
                'secondary_product_id' => $secondaryItem->product_id,
                'price' => $defaultPrice['price'] ?? 0,
                'mode_prices' => $modePrices,
            ];
        }

        $secondaryProducts = $secondaryProducts->unique('id')->values();

        return response()->json([
            'secondaries' => $secondaryProducts,
            'pairs' => $pairs
        ]);
    }

    public function show(EcommerceProduct $product)
    {
        $product->load([
            'categories',
            'unit',
            'priceModes',
            'variantGroups.options.product',
            'variantCombinations.productOption',
            'variantCombinations.lidOption',
        ]);

        $allowedModeIds = $product->priceModes->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($product->variantCombinations as $combination) {
            $primaryProductId = $combination->productOption?->product_id;
            $secondaryProductId = $combination->lidOption?->product_id;
            $bundle = $primaryProductId && $secondaryProductId
                ? $this->pricingService->bundleForPair((int) $primaryProductId, (int) $secondaryProductId)
                : null;
            $modePrices = $bundle
                ? $this->pricingService->bundleModePrices($bundle, (int) $product->unit_id)
                : [];
            $combination->setAttribute('mode_prices', array_values(array_filter(
                $modePrices,
                fn ($modePrice) => in_array($modePrice['price_mode_id'], $allowedModeIds, true)
            )));
        }

        return view('erp.pages.ecommerce-products.detail-product', compact('product'));
    }

    public function store(EcommerceProductStoreRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $product = EcommerceProduct::create($this->productPayload($request));

                $product->categories()->sync($request->category_ids);
                $product->priceModes()->sync($request->price_mode_ids);
                $this->syncVariantGroups($product, $request);
                $this->syncVariantCombinations($product, $request);
                $this->syncBasePrice($product);
                $this->syncGalleryImages($product, $request);
            });

            return redirect()
                ->route('erp.ecommerce-products.index')
                ->with('success', 'Ecommerce Product berhasil dibuat.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal membuat Ecommerce Product: ' . $e->getMessage());
        }
    }

    public function edit(EcommerceProduct $product)
    {
        $product->load([
            'variantGroups.options.product',
            'variantCombinations',
            'priceModes',
        ]);

        return view('erp.pages.ecommerce-products.edit-product', array_merge(
            $this->formData(),
            compact('product')
        ));
    }

    public function update(EcommerceProductUpdateRequest $request, EcommerceProduct $product)
    {
        try {
            DB::transaction(function () use ($request, $product) {
                $product->update($this->productPayload($request, $product));

                $product->categories()->sync($request->category_ids);
                $product->priceModes()->sync($request->price_mode_ids);
                $this->syncVariantGroups($product, $request);
                $this->syncVariantCombinations($product, $request);
                $this->syncBasePrice($product);
                $this->syncGalleryImages($product, $request);
            });

            return redirect()
                ->route('erp.ecommerce-products.index')
                ->with('success', 'Ecommerce Product berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui Ecommerce Product: ' . $e->getMessage());
        }
    }

    public function destroy(EcommerceProduct $product)
    {
        DB::transaction(function () use ($product) {
            $groupIds = $product->variantGroups()->pluck('id');

            EcommerceVariantOption::whereIn('variant_group_id', $groupIds)->delete();

            $product->delete();
        });

        return redirect()
            ->route('erp.ecommerce-products.index')
            ->with('success', 'Ecommerce Product berhasil dihapus.');
    }

    public function restore($id)
    {
        DB::transaction(function () use ($id) {
            $product = EcommerceProduct::onlyTrashed()->findOrFail($id);
            $product->restore();

            $groupIds = $product->variantGroups()->pluck('id');

            EcommerceVariantOption::withTrashed()
                ->whereIn('variant_group_id', $groupIds)
                ->restore();
        });

        return redirect()
            ->route('erp.ecommerce-products.index')
            ->with('success', 'Ecommerce Product berhasil direstore.');
    }

    private function formData(): array
    {
        return [
            'categories' => EcommerceProductCategory::orderBy('name')->get(),
            'productUnits' => ProductUnit::orderBy('name')->get(),
            'priceModes' => \App\Models\PriceMode::active()->ordered()->get(),
            'erpProducts' => Products::with('unitConversions.prices.priceMode')->orderBy('name')->get(),
        ];
    }

    private function productPayload(Request $request, ?EcommerceProduct $product = null): array
    {
        return [
            'unit_id' => $request->unit_id,
            'title' => $request->title,
            'slug' => $request->slug,
            'brand' => $request->brand,
            'main_image' => $this->storeFile($request->file('main_image'), $product?->main_image),
            'main_video' => $this->storeFile($request->file('main_video'), $product?->main_video),
            'description' => $request->description,
            'multiple_qty' => $request->multiple_qty,
            'min_qty' => $request->min_qty,
            'max_qty' => $request->filled('max_qty') ? $request->max_qty : null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ];
    }

    private function syncVariantGroups(EcommerceProduct $product, Request $request): void
    {
        $keptGroupIds = [];
        $allowedModeIds = array_map('intval', $request->input('price_mode_ids', []));

        foreach ($request->input('variant_groups', []) as $groupIndex => $groupData) {
            $group = null;

            if (!empty($groupData['id'])) {
                $group = $product->variantGroups()
                    ->where('id', $groupData['id'])
                    ->first();
            }

            if ($group) {
                $group->update([
                    'name' => $groupData['name'],
                    'sort_order' => 0,
                ]);
            } else {
                $group = $product->variantGroups()->create([
                    'name' => $groupData['name'],
                    'sort_order' => 0,
                ]);
            }

            $keptGroupIds[] = $group->id;
            $keptOptionIds = [];

            foreach ($groupData['options'] ?? [] as $optionIndex => $optionData) {
                $option = null;

                if (!empty($optionData['id'])) {
                    $option = $group->options()
                        ->withTrashed()
                        ->where('id', $optionData['id'])
                        ->first();
                }

                $oldImage = $option?->image;

                $erpProduct = null;
                $price = 0;
                if (!empty($optionData['product_id'])) {
                    $erpProduct = Products::with('unitConversions.prices.priceMode')
                        ->find($optionData['product_id']);
                    if ($erpProduct) {
                        $modePrices = $this->pricingService->productModePrices(
                            $erpProduct,
                            (int) $request->unit_id
                        );                        $modePrices = array_values(array_filter(
                            $modePrices,
                            fn ($modePrice) => in_array($modePrice['price_mode_id'], $allowedModeIds, true)
                        ));
                        $price = $this->pricingService->defaultPrice($modePrices)['price'] ?? 0;
                    }
                }

                $payload = [
                    'product_id' => $optionData['product_id'] ?? null,
                    'alias' => $optionData['alias'],
                    'price' => $price,
                    'image' => $this->storeFile(
                        $request->file("variant_groups.$groupIndex.options.$optionIndex.image"),
                        $oldImage
                    ),
                    'is_active' => $optionData['is_active'] ?? false,
                    'allow_without_lid' => $optionData['allow_without_lid'] ?? false,
                ];

                if ($option) {
                    if ($option->trashed()) {
                        $option->restore();
                    }

                    $option->update($payload);
                } else {
                    $option = $group->options()->create($payload);
                }

                $keptOptionIds[] = $option->id;
            }

            $group->options()
                ->whereNotIn('id', $keptOptionIds)
                ->delete();
        }

        $product->variantGroups()
            ->whereNotIn('id', $keptGroupIds)
            ->get()
            ->each(function ($group) {
                $group->options()->delete();
                $group->delete();
            });

    }

    private function syncVariantCombinations(EcommerceProduct $product, Request $request): void
    {
        $keptCombinationIds = [];
        $allowedModeIds = array_map('intval', $request->input('price_mode_ids', []));
        $combinations = $request->input('variant_combinations', []);
        
        // Refresh product to get newly created groups and options
        $product->load('variantGroups.options');
        
        $groups = $product->variantGroups;
        if ($groups->count() < 2) {
            // If less than 2 groups, no combinations are possible
            $product->variantCombinations()->delete();
            return;
        }

        $productGroup = $groups[0];
        $lidGroup = $groups[1];

        foreach ($combinations as $index => $combData) {
            if (empty($combData['product_option_product_id']) || empty($combData['lid_option_product_id'])) {
                continue;
            }

            $productOption = $productGroup->options->firstWhere('product_id', $combData['product_option_product_id']);
            $lidOption = $lidGroup->options->firstWhere('product_id', $combData['lid_option_product_id']);

            if (!$productOption || !$lidOption) {
                continue; // Skip if options were not saved or found
            }

            $combination = null;

            if (!empty($combData['id'])) {
                $combination = $product->variantCombinations()
                    ->where('id', $combData['id'])
                    ->first();
            }

            $bundle = $this->pricingService->bundleForPair(
                (int) $combData['product_option_product_id'],
                (int) $combData['lid_option_product_id']
            );
            $modePrices = $bundle
                ? $this->pricingService->bundleModePrices($bundle, (int) $request->unit_id)
                : [];            $modePrices = array_values(array_filter(
                $modePrices,
                fn ($modePrice) => in_array($modePrice['price_mode_id'], $allowedModeIds, true)
            ));
            $price = $this->pricingService->defaultPrice($modePrices)['price'] ?? 0;

            $oldImage = $combination?->image;
            $payload = [
                'product_option_id' => $productOption->id,
                'lid_option_id' => $lidOption->id,
                'price' => $price,
                'image' => $this->storeFile(
                    $request->file("variant_combinations.$index.image"),
                    $oldImage
                ),
                'is_active' => $combData['is_active'] ?? false,
                'sort_order' => $index,
            ];

            if ($combination) {
                $combination->update($payload);
            } else {
                $combination = $product->variantCombinations()->create($payload);
            }

            $keptCombinationIds[] = $combination->id;
        }

        $product->variantCombinations()
            ->whereNotIn('id', $keptCombinationIds)
            ->delete();
    }

    private function storeFile($file, ?string $oldPath = null): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        if ($oldPath && file_exists(public_path('uploads/' . $oldPath))) {
            unlink(public_path('uploads/' . $oldPath));
        }

        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/ecommerce-products'), $filename);

        return 'ecommerce-products/' . $filename;
    }

    private function syncBasePrice(EcommerceProduct $product): void
    {
        $minPrice = 0;
        
        $firstGroup = $product->variantGroups()->first();
        
        if ($firstGroup) {
            $optionPrices = \App\Models\EcommerceVariantOption::where('variant_group_id', $firstGroup->id)
                ->pluck('price')
                ->filter(function ($price) {
                    return $price > 0;
                });
            
            if ($optionPrices->isNotEmpty()) {
                $minPrice = $optionPrices->min();
            }
        }

        $product->update(['price' => $minPrice]);
    }

    private function syncGalleryImages(EcommerceProduct $product, Request $request): void
    {
        // 1. Delete gallery images requested by user
        if ($request->has('delete_gallery_images')) {
            $imagesToDelete = EcommerceProductImage::whereIn('id', $request->delete_gallery_images)->get();
            foreach ($imagesToDelete as $img) {
                if ($img->image && file_exists(public_path('uploads/' . $img->image))) {
                    unlink(public_path('uploads/' . $img->image));
                }
                $img->delete();
            }
        }

        // 2. Add new gallery images
        if ($request->hasFile('gallery_images')) {
            $sortOrder = $product->galleryImages()->max('sort_order') ?? 0;
            foreach ($request->file('gallery_images') as $file) {
                $sortOrder++;
                $path = $this->storeFile($file);
                if ($path) {
                    $product->galleryImages()->create([
                        'image' => $path,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }
    }
}
