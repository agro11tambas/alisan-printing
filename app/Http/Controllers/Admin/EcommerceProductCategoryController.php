<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceProductCategoryStoreRequest;
use App\Http\Requests\EcommerceProductCategoryUpdateRequest;
use App\Models\EcommerceProductCategory;
use App\Services\WebsiteRevalidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class EcommerceProductCategoryController extends Controller
{
    public function __construct(private WebsiteRevalidator $websiteRevalidator)
    {
    }

    public function index()
    {
        $mainCategoryOptions = EcommerceProductCategory::root()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('erp.pages.ecommerce-product-categories.index', compact('mainCategoryOptions'));
    }

    public function data(Request $request)
    {
        $query = EcommerceProductCategory::query()->with('parent')->withCount('children');

        // Tab main category cuma nampilin yang tanpa parent, tab sub category kebalikannya.
        if ($request->input('scope') === 'root') {
            $query->root();
        } elseif ($request->input('scope') === 'sub') {
            $query->whereNotNull('parent_id');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

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

                $url = $category->image_url;

                return '<a href="' . $url . '" data-lightbox="category-' . $category->id . '">
                    <img src="' . $url . '" class="rounded" style="width:48px;height:48px;object-fit:cover;" alt="Category Image">
                </a>';
            })
            ->addColumn('name', fn($category) => e($category->name))
            ->addColumn('parent', function ($category) {
                return $category->parent
                    ? '<span class="badge bg-soft-primary text-primary">' . e($category->parent->name) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('subcategories', function ($category) {
                return $category->children_count > 0
                    ? '<span class="badge bg-soft-success text-success">' . $category->children_count . ' sub</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('slug', fn($category) => e($category->slug))
            ->addColumn('description', fn($category) => e(Str::limit($category->description ?? '-', 80)))
            ->addColumn('action', function ($category) {
                return view('erp.pages.ecommerce-product-categories.partials.action-button', compact('category'))->render();
            })
            ->rawColumns(['image', 'parent', 'subcategories', 'action'])
            ->make(true);
    }

    public function create(Request $request)
    {
        // Sub category punya form sendiri: cukup pilih main category-nya, tidak
        // perlu lewat form main category lagi.
        if ($request->input('type') === 'sub') {
            return view('erp.pages.ecommerce-product-categories.create-subcategory', [
                'mainCategoryOptions' => $this->mainCategoryOptions(),
            ]);
        }

        $subcategoryOptions = $this->subcategoryOptions();

        return view('erp.pages.ecommerce-product-categories.create-category', compact('subcategoryOptions'));
    }

    public function store(EcommerceProductCategoryStoreRequest $request)
    {
        $parentId = $request->validated('parent_id');

        DB::transaction(function () use ($request, $parentId) {
            $category = EcommerceProductCategory::create([
                'parent_id' => $parentId,
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'description' => $request->validated('description'),
                'image' => $this->storeFile($request->file('image')),
                'is_active' => true,
                'sort_order' => 0,
            ]);

            // Struktur category dibatasi dua level, jadi sub category tidak
            // punya sub category sendiri.
            if (! $parentId) {
                $this->syncSubcategories($category, $request);
            }

            $this->websiteRevalidator->categories();
        });

        return redirect()
            ->route('erp.ecommerce-product-categories.index', $parentId ? ['tab' => 'sub'] : [])
            ->with('success', $parentId
                ? 'Ecommerce Product Sub Category berhasil dibuat.'
                : 'Ecommerce Product Category berhasil dibuat.');
    }

    public function edit(EcommerceProductCategory $category)
    {
        if ($category->parent_id) {
            return view('erp.pages.ecommerce-product-categories.edit-subcategory', [
                'category' => $category,
                'mainCategoryOptions' => $this->mainCategoryOptions($category),
            ]);
        }

        $category->load('children');

        $subcategoryOptions = $this->subcategoryOptions($category);

        return view('erp.pages.ecommerce-product-categories.edit-category', compact('category', 'subcategoryOptions'));
    }

    public function update(EcommerceProductCategoryUpdateRequest $request, EcommerceProductCategory $category)
    {
        $parentId = $request->validated('parent_id');

        DB::transaction(function () use ($request, $category, $parentId) {
            $category->update([
                'parent_id' => $parentId,
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'description' => $request->validated('description'),
                'image' => $this->storeFile($request->file('image'), $category->image),
                'is_active' => true,
                'sort_order' => 0,
            ]);

            if (! $parentId) {
                $this->syncSubcategories($category, $request);
            }

            $this->websiteRevalidator->categories();
        });

        return redirect()
            ->route('erp.ecommerce-product-categories.index', $this->indexTabFor($category))
            ->with('success', $parentId
                ? 'Ecommerce Product Sub Category berhasil diperbarui.'
                : 'Ecommerce Product Category berhasil diperbarui.');
    }

    public function destroy(EcommerceProductCategory $category)
    {
        // Soft delete tidak men-trigger FK nullOnDelete, jadi child-nya dinaikkan
        // manual ke parent di atasnya supaya tidak nyangkut ke category terhapus.
        $category->children()->update(['parent_id' => $category->parent_id]);

        $category->delete();

        $this->websiteRevalidator->categories();

        return redirect()
            ->route('erp.ecommerce-product-categories.index', $this->indexTabFor($category))
            ->with('success', 'Ecommerce Product Category berhasil dihapus.');
    }

    public function restore($id)
    {
        $category = EcommerceProductCategory::onlyTrashed()->findOrFail($id);
        $category->restore();

        $this->websiteRevalidator->categories();

        return redirect()
            ->route('erp.ecommerce-product-categories.index', $this->indexTabFor($category))
            ->with('success', 'Ecommerce Product Category berhasil direstore.');
    }

    /**
     * Balik ke tab yang sesuai: sub category ke tab Sub Category,
     * sisanya ke tab Main Category.
     */
    private function indexTabFor(EcommerceProductCategory $category): array
    {
        return $category->parent_id ? ['tab' => 'sub'] : [];
    }

    /**
     * Simpan sub category dari form main category:
     * - category existing yang dipilih dipindah jadi child di sini
     * - sub category yang dilepas dari list dikembalikan jadi main category (tidak dihapus)
     * - baris sub category baru dibuat sebagai child
     */
    private function syncSubcategories(EcommerceProductCategory $category, Request $request): void
    {
        // Category ini sendiri dan ancestor-nya tidak boleh jadi child (bikin siklus).
        $forbiddenIds = array_merge([$category->id], $category->ancestorIds());

        $keepIds = collect($request->input('existing_child_ids', []))
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => in_array($id, $forbiddenIds, true))
            ->unique()
            ->values()
            ->all();

        // Rename sub category existing yang diedit langsung di form
        foreach ($request->input('existing_children', []) as $childId => $childInput) {
            $childId = (int) $childId;

            if (!in_array($childId, $keepIds, true)) {
                continue;
            }

            $name = trim($childInput['name'] ?? '');

            if ($name === '') {
                continue;
            }

            EcommerceProductCategory::where('id', $childId)->update(['name' => $name]);
        }

        EcommerceProductCategory::where('parent_id', $category->id)
            ->whereNotIn('id', $keepIds)
            ->update(['parent_id' => null]);

        if ($keepIds) {
            EcommerceProductCategory::whereIn('id', $keepIds)->update(['parent_id' => $category->id]);
        }

        foreach ($request->input('subcategories', []) as $index => $subcategoryInput) {
            $name = trim($subcategoryInput['name'] ?? '');

            if ($name === '') {
                continue;
            }

            EcommerceProductCategory::create([
                'parent_id' => $category->id,
                'name' => $name,
                'slug' => EcommerceProductCategory::generateUniqueSlug($name),
                'description' => $subcategoryInput['description'] ?? null,
                'image' => $this->storeFile($request->file("subcategories.{$index}.image")),
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        $category->unsetRelation('children');
    }

    /**
     * Kandidat main category untuk form sub category: category tanpa parent,
     * kecuali category yang sedang diedit sendiri.
     *
     * @return \Illuminate\Support\Collection<int, EcommerceProductCategory>
     */
    private function mainCategoryOptions(?EcommerceProductCategory $category = null)
    {
        return EcommerceProductCategory::root()
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Kandidat sub category: semua category yang belum punya turunan sendiri
     * (biar struktur tetap dua level), kecuali category ini dan ancestor-nya.
     */
    private function subcategoryOptions(?EcommerceProductCategory $category = null): array
    {
        $forbiddenIds = $category ? array_merge([$category->id], $category->ancestorIds()) : [];

        return EcommerceProductCategory::query()
            ->with('parent')
            ->withCount('children')
            ->whereNotIn('id', $forbiddenIds)
            ->orderBy('name')
            ->get()
            ->filter(fn ($item) => $item->children_count === 0)
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'parent_name' => $item->parent?->name,
            ])
            ->values()
            ->all();
    }

    private function storeFile($file, ?string $oldPath = null): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        if ($oldPath) {
            $publicPath = public_path('uploads/' . $oldPath);

            if (File::exists($publicPath)) {
                File::delete($publicPath);
            } else {
                // Hapus file lama yang masih tersimpan lewat disk storage.
                Storage::disk('public')->delete($oldPath);
            }
        }

        $directory = public_path('uploads/ecommerce-categories');
        File::ensureDirectoryExists($directory);

        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'ecommerce-categories/' . $filename;
    }
}
