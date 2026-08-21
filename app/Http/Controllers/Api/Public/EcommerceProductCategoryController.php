<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\EcommerceCatalogCache;
use Illuminate\Http\Request;

use App\Models\EcommerceProductCategory;

class EcommerceProductCategoryController extends Controller
{
    /** @var array<string, string|null> */
    private array $imageUrlMemo = [];

    public function __construct(private EcommerceCatalogCache $catalogCache)
    {
    }

    public function index(Request $request)
    {
        // ?tree=1 -> susun jadi nested (parent beserta children-nya).
        // Tanpa parameter, response tetap flat seperti sebelumnya (kini ada parent_id).
        $tree = $request->boolean('tree');

        // Endpoint ini dipanggil website di hampir tiap halaman dan sebelumnya
        // sama sekali tidak di-cache, tidak seperti endpoint produk: tiap
        // panggilan mengulang query plus dua file_exists() per kategori. Cache-nya
        // dikosongkan WebsiteRevalidator saat admin menyimpan kategori, jadi
        // datanya tidak akan basi.
        $json = $this->catalogCache->rememberJson('categories:index:'.($tree ? 'tree' : 'flat').':json', function () use ($tree) {
            $categories = EcommerceProductCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->get();

            $data = $categories->toArray();

            foreach ($data as &$category) {
                $category['image'] = $this->resolveImageUrl($category['image'] ?? null);
            }
            unset($category);

            return [
                'success' => true,
                'data' => $tree ? $this->buildTree($data) : $data,
            ];
        });

        return response($json)->header('Content-Type', 'application/json');
    }

    private function buildTree(array $categories, $parentId = null): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if (($category['parent_id'] ?? null) != $parentId) {
                continue;
            }

            $category['children'] = $this->buildTree($categories, $category['id']);
            $branch[] = $category;
        }

        return $branch;
    }

    /**
     * Hasilnya diingat per request. Tiap panggilan melakukan sampai dua
     * file_exists(), dan di shared hosting stat filesystem tidak murah.
     */
    private function resolveImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return $image;
        }

        if (str_starts_with($image, 'http')) {
            // Already a full URL
            return $image;
        }

        if (array_key_exists($image, $this->imageUrlMemo)) {
            return $this->imageUrlMemo[$image];
        }

        if (file_exists(public_path('uploads/' . $image))) {
            return $this->imageUrlMemo[$image] = asset('uploads/' . $image);
        }

        if (file_exists(public_path('storage/' . $image))) {
            return $this->imageUrlMemo[$image] = asset('storage/' . $image);
        }

        // Path kategori sekarang selalu mengarah ke public/uploads. Ini juga
        // membuat URL konsisten walaupun filesystem production tidak bisa di-stat.
        return $this->imageUrlMemo[$image] = asset('uploads/' . $image);
    }
}
