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
            ->get();
            
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
            'data' => $product
        ]);
    }
}
