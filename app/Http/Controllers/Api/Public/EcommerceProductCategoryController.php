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
            
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
