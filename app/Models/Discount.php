<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;
use App\Models\ProductCategory;
use App\Models\EcommerceProductCategory;

class Discount extends Model
{
    protected $table = 'discounts';

    /**
     * Scope "Apply On" baku, dalam urutan penyimpanan.
     *
     * Scope-nya tidak lagi dipilih di form: tiap diskon baru selalu memakai
     * keduanya. Semantiknya AND — baris order kena diskon kalau produknya masuk
     * kategori yang dipilih DAN mode barisnya termasuk mode yang dipilih.
     */
    public const SCOPES = ['Category', 'Mode'];

    /**
     * Scope yang masih bisa dievaluasi, termasuk "Product" dari data lama.
     *
     * "Product" sudah tidak ditawarkan di form, tapi diskon lama yang masih
     * memakainya tetap dihitung seperti biasa sampai diskonnya disimpan ulang.
     */
    public const MATCHABLE_SCOPES = ['Product', 'Category', 'Mode'];

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
     * Daftar scope diskon ini, hasil pecah kolom `apply_on` yang dipisah koma.
     */
    public function getApplyOnListAttribute(): array
    {
        return static::parseScopes($this->apply_on);
    }

    public function appliesOn(string $scope): bool
    {
        return in_array($scope, $this->apply_on_list, true);
    }

    /**
     * Ubah input (array atau string koma) jadi daftar scope yang valid dan urut.
     */
    public static function parseScopes($value, array $allowed = self::MATCHABLE_SCOPES): array
    {
        $items = is_array($value)
            ? $value
            : explode(',', (string) $value);

        $items = array_filter(array_map('trim', $items));

        return array_values(array_filter(
            $allowed,
            fn ($scope) => in_array($scope, $items, true)
        ));
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
     * Saring diskon yang punya scope tertentu di dalam daftar `apply_on`.
     *
     * Dicocokkan per elemen (bukan LIKE polos) supaya scope yang namanya
     * mengandung nama scope lain tidak saling tertukar.
     */
    public function scopeHasApplyOn($query, string $scope)
    {
        return $query->where(function ($q) use ($scope) {
            $q->where('apply_on', $scope)
                ->orWhere('apply_on', 'like', $scope.',%')
                ->orWhere('apply_on', 'like', '%,'.$scope)
                ->orWhere('apply_on', 'like', '%,'.$scope.',%');
        });
    }

    /**
     * Bentuk diskon yang dipakai form order (JS) dan evaluator di server.
     *
     * Semua target dikirim sekaligus — produk, kategori, mode — supaya
     * penerimanya bisa menguji tiap scope tanpa perlu tahu lewat jalur mana
     * diskon ini sampai.
     */
    public function toDiscountPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'minimum_based_on' => $this->minimum_based_on,
            'minimum_qty_or_amount' => (float) $this->minimum_qty_or_amount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => (int) $this->is_active,
            'apply_on' => $this->apply_on,
            'apply_on_list' => $this->apply_on_list,
            'product_ids' => $this->relationLoaded('products')
                ? $this->products->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'category_ids' => $this->relationLoaded('categories')
                ? $this->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'price_mode_slugs' => $this->relationLoaded('priceModes')
                ? $this->priceModes->pluck('slug')->values()->all()
                : [],
        ];
    }

    /**
     * Relasi yang wajib ikut supaya `toDiscountPayload()` lengkap.
     */
    public static function payloadRelations(string $prefix = ''): array
    {
        return [
            $prefix.'products:id',
            $prefix.'categories:id',
            $prefix.'priceModes:id,slug',
        ];
    }

    /**
     * Diskon ber-scope "Mode" untuk form order.
     *
     * Ini jalur masuk satu-satunya bagi diskon yang tidak punya pivot produk
     * maupun kategori, jadi tetap dibutuhkan meski scope-nya sekarang jamak.
     */
    public static function modeDiscountsPayload(): array
    {
        return static::with(static::payloadRelations())
            ->running()
            ->hasApplyOn('Mode')
            ->get()
            ->map(fn ($discount) => $discount->toDiscountPayload())
            ->values()
            ->toArray();
    }
}
