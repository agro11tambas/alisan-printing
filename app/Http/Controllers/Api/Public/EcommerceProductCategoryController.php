<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EcommerceProductCategory;

class EcommerceProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = EcommerceProductCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $data = $categories->toArray();
        foreach ($data as &$category) {
            $category['image'] = $this->resolveImageUrl($category['image'] ?? null);
        }
        unset($category);

        // ?tree=1 -> susun jadi nested (parent beserta children-nya).
        // Tanpa parameter, response tetap flat seperti sebelumnya (kini ada parent_id).
        if ($request->boolean('tree')) {
            $data = $this->buildTree($data);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
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

    private function resolveImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return $image;
        }

        if (str_starts_with($image, 'http')) {
            // Already a full URL
            return $image;
        }

        if (file_exists(public_path('storage/' . $image))) {
            return asset('storage/' . $image);
        }

        if (file_exists(public_path('uploads/' . $image))) {
            return asset('uploads/' . $image);
        }

        return asset('storage/' . $image);
    }
}
