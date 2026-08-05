<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;
use App\Models\ProductCategory;
use App\Models\EcommerceProductCategory;

class Discount extends Model
{
    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'type',
        'amount',
        'minimum_based_on',
        'minimum_qty_or_amount',
        'start_date',
        'end_date',
        'is_active',
        'apply_on',
        'apply_on_ecommerce',
    ];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'discount_products', 'discount_id', 'product_id');
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'discount_categories', 'discount_id', 'category_id');
    }

    public function ecommerceCategories()
    {
        return $this->belongsToMany(EcommerceProductCategory::class, 'discount_ecommerce_categories', 'discount_id', 'ecommerce_product_category_id');
    }

    public function priceModes()
    {
        return $this->belongsToMany(PriceMode::class, 'discount_price_modes', 'discount_id', 'price_mode_id');
    }

    /**
     * Diskon yang sedang berlaku hari ini (aktif + di dalam rentang tanggal).
     */
    public function scopeRunning($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', 1)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today));
    }

    /**
     * Diskon "Apply On: Mode" untuk dipakai form order (JS), lengkap dengan slug mode-nya.
     */
    public static function modeDiscountsPayload(): array
    {
        return static::with('priceModes')
            ->running()
            ->where('apply_on', 'Mode')
            ->get()
            ->map(fn ($discount) => array_merge(
                collect($discount->toArray())->except('price_modes')->all(),
                ['price_mode_slugs' => $discount->priceModes->pluck('slug')->values()->all()]
            ))
            ->values()
            ->toArray();
    }
}
