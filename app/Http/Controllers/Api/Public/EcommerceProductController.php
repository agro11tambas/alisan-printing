<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EcommerceProduct;

class EcommerceProductController extends Controller
{
    public function index()
    {
        $products = EcommerceProduct::with([
                'categories',
                'galleryImages',
                'variantGroups.options.product.categories',
                'variantCombinations.productOption.product',
                'variantCombinations.lidOption.product'
            ])
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
            'galleryImages',
            'variantGroups.options.product.categories', 
            'variantCombinations.productOption.product',
            'variantCombinations.lidOption.product'
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
        // Calculate dynamic prices for options based on live ERP product sale_price
        if ($product->relationLoaded('variantGroups')) {
            foreach ($product->variantGroups as $group) {
                foreach ($group->options as $opt) {
                    $originalPrice = (float)$opt->price;
                    
                    if ($opt->relationLoaded('product') && $opt->product && (float)$opt->product->sale_price > 0) {
                        $opt->original_price = $originalPrice;
                        $opt->sale_price = (float)$opt->product->sale_price + (float)$opt->extra_price;
                    } else {
                        $opt->original_price = $originalPrice;
                        $opt->sale_price = null;
                    }
                }
            }
        }

        // Calculate dynamic prices for combinations based on live ERP product sale_price
        if ($product->relationLoaded('variantCombinations')) {
            foreach ($product->variantCombinations as $comb) {
                $originalPrice = 0;
                $salePrice = 0;
                $hasDiscount = false;
                
                if ($comb->relationLoaded('productOption') && $comb->productOption) {
                    $optPrice = (float)$comb->productOption->price;
                    $originalPrice += $optPrice;
                    
                    $erpSalePrice = 0;
                    if ($comb->productOption->relationLoaded('product') && $comb->productOption->product) {
                        $erpSalePrice = (float)$comb->productOption->product->sale_price;
                    }
                    
                    if ($erpSalePrice > 0) {
                        $salePrice += $erpSalePrice + (float)$comb->productOption->extra_price;
                        $hasDiscount = true;
                    } else {
                        $salePrice += $optPrice;
                    }
                }
                
                if ($comb->relationLoaded('lidOption') && $comb->lidOption) {
                    $optPrice = (float)$comb->lidOption->price;
                    $originalPrice += $optPrice;
                    
                    $erpSalePrice = 0;
                    if ($comb->lidOption->relationLoaded('product') && $comb->lidOption->product) {
                        $erpSalePrice = (float)$comb->lidOption->product->sale_price;
                    }
                    
                    if ($erpSalePrice > 0) {
                        $salePrice += $erpSalePrice + (float)$comb->lidOption->extra_price;
                        $hasDiscount = true;
                    } else {
                        $salePrice += $optPrice;
                    }
                }

                if ($originalPrice > 0) {
                    $comb->original_price = $originalPrice;
                    $comb->price = $originalPrice; // By default price is original
                } else {
                    $comb->original_price = (float)$comb->price;
                }
                
                // Add explicit sale_price to combination to be sent via API
                $comb->sale_price = $hasDiscount && $salePrice > 0 && $salePrice < $originalPrice ? $salePrice : null;
            }
        }

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
        if (isset($data['gallery_images'])) {
            foreach ($data['gallery_images'] as &$gallery) {
                if (!empty($gallery['image'])) {
                    $gallery['image_url'] = $this->resolveImageUrl($gallery['image']);
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
                        if (isset($opt['product'])) {
                            $opt['erp_product_id'] = $opt['product']['id'];
                            if (isset($opt['product']['categories'])) {
                                $opt['erp_category_ids'] = collect($opt['product']['categories'])->pluck('id')->toArray();
                            }
                        }
                        unset($opt['product']);
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
                // Clean up nested relations to keep payload clean
                unset($variant['product_option']['product']);
                unset($variant['lid_option']['product']);
            }
        }
        return $data;
    }
}
