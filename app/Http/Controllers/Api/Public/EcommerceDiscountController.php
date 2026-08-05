<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Discount;

class EcommerceDiscountController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $discounts = Discount::with(['products', 'categories', 'ecommerceCategories', 'priceModes'])
            ->where('is_active', 1)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->get()
            ->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'type' => $discount->type, // Percentage / Fixed Amount
                    'amount' => (float) $discount->amount,
                    'minimum_based_on' => $discount->minimum_based_on,
                    'minimum_qty_or_amount' => (float) $discount->minimum_qty_or_amount,
                    'start_date' => $discount->start_date,
                    'end_date' => $discount->end_date,
                    'apply_on' => $discount->apply_on,
                    'apply_on_ecommerce' => $discount->apply_on_ecommerce,
                    'products' => $discount->products->pluck('id'),
                    'categories' => $discount->categories->pluck('id'),
                    'ecommerce_categories' => $discount->ecommerceCategories->pluck('id'),
                    'price_modes' => $discount->priceModes->pluck('id'),
                    'price_mode_slugs' => $discount->priceModes->pluck('slug'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }
}
