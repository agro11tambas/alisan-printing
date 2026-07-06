<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceProductStoreRequest;
use App\Http\Requests\EcommerceProductUpdateRequest;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductCategory;
use App\Models\EcommerceVariantOption;
use App\Models\Products;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class EcommerceProductController extends Controller
{
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

        $query->orderByDesc('created_at');

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
            ->addColumn('title', fn($product) => e($product->title))
            ->addColumn('status', function ($product) {
                if ($product->is_active) {
                    return '<span class="badge bg-success">Active</span>';
                }
                return '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('category', function ($product) {
                if ($product->categories->isEmpty()) return '-';
                return $product->categories->pluck('name')->map(fn($name) => e($name))->implode(', ');
            })
            ->addColumn('unit', fn($product) => e($product->unit?->name ?? '-'))
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

    public function show(EcommerceProduct $product)
    {
        $product->load([
            'categories',
            'unit',
            'variantGroups.options.product',
        ]);

        return view('erp.pages.ecommerce-products.detail-product', compact('product'));
    }

    public function store(EcommerceProductStoreRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $product = EcommerceProduct::create($this->productPayload($request));

                $product->categories()->sync($request->category_ids);
                $this->syncVariantGroups($product, $request);
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
                $this->syncVariantGroups($product, $request);
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
            'erpProducts' => Products::orderBy('name')->get(),
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
                $oldVideo = $option?->video;

                $payload = [
                    'product_id' => $optionData['product_id'] ?? null,
                    'alias' => $optionData['alias'],
                    'image' => $this->storeFile(
                        $request->file("variant_groups.$groupIndex.options.$optionIndex.image"),
                        $oldImage
                    ),
                    'video' => $this->storeFile(
                        $request->file("variant_groups.$groupIndex.options.$optionIndex.video"),
                        $oldVideo
                    ),
                    'is_active' => true,
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

}
