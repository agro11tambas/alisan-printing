<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EcommerceProduct;

class EcommerceProductController extends Controller
{
    public function index()
    {
        $products = EcommerceProduct::with(['categories'])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($product) {
                return $this->formatProduct($product);
            });
            
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function show($slug)
    {
        $product = EcommerceProduct::with([
            'categories', 
            'variantGroups.options', 
            'variantCombinations'
        ])
        ->where('slug', $slug)
        ->where('is_active', true)
        ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product)
        ]);
    }

    private function resolveImageUrl($path) {
        if (empty($path)) return $path;
        if (str_starts_with($path, 'http')) return $path;
        
        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        } else if (file_exists(public_path('uploads/' . $path))) {
            return asset('uploads/' . $path);
        }
        
        return asset('storage/' . $path);
    }

    private function formatProduct($product)
    {
        $data = $product->toArray();
        if (!empty($data['main_image'])) {
            $data['main_image'] = $this->resolveImageUrl($data['main_image']);
        }
        if (!empty($data['main_video'])) {
            $data['main_video'] = $this->resolveImageUrl($data['main_video']);
        }
        if (isset($data['categories'])) {
            foreach ($data['categories'] as &$category) {
                if (!empty($category['image'])) {
                    $category['image'] = $this->resolveImageUrl($category['image']);
                }
            }
        }
        if (isset($data['variant_groups'])) {
            foreach ($data['variant_groups'] as &$group) {
                if (isset($group['options'])) {
                    foreach ($group['options'] as &$opt) {
                        if (!empty($opt['image'])) {
                            $opt['image'] = $this->resolveImageUrl($opt['image']);
                        }
                    }
                }
            }
        }
        if (isset($data['variant_combinations'])) {
            foreach ($data['variant_combinations'] as &$variant) {
                if (!empty($variant['image'])) {
                    $variant['image'] = $this->resolveImageUrl($variant['image']);
                }
                if (!empty($variant['video'])) {
                    $variant['video'] = $this->resolveImageUrl($variant['video']);
                }
            }
        }
        return $data;
    }
}
