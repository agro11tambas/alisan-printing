<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EcommerceProductCategory;

class EcommerceProductCategoryController extends Controller
{
    public function index()
    {
        $categories = EcommerceProductCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
            
        $data = $categories->toArray();
        foreach ($data as &$category) {
            if (!empty($category['image'])) {
                if (str_starts_with($category['image'], 'http')) {
                    // Already a full URL
                } else if (file_exists(public_path('storage/' . $category['image']))) {
                    $category['image'] = asset('storage/' . $category['image']);
                } else if (file_exists(public_path('uploads/' . $category['image']))) {
                    $category['image'] = asset('uploads/' . $category['image']);
                } else {
                    $category['image'] = asset('storage/' . $category['image']);
                }
            }
        }
            
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
