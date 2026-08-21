<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EcommerceProductCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'image_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function products()
    {
        return $this->belongsToMany(EcommerceProduct::class, 'ecommerce_product_category_pivot', 'ecommerce_product_category_id', 'ecommerce_product_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ID kategori ini beserta seluruh turunannya. Dipakai untuk mencegah
     * sebuah kategori dijadikan child dari keturunannya sendiri (siklus).
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /**
     * ID seluruh ancestor (parent, kakeknya, dst). Dipakai untuk mencegah
     * sebuah ancestor dijadikan sub category dari turunannya sendiri.
     */
    public function ancestorIds(): array
    {
        $ids = [];
        $parent = $this->parent;

        while ($parent) {
            $ids[] = $parent->id;
            $parent = $parent->parent;
        }

        return $ids;
    }

    /**
     * Slug unik dari sebuah nama. Dipakai waktu sub category dibuat langsung
     * dari form main category, jadi user tidak perlu isi slug manual.
     */
    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function getImageUrlAttribute()
    {
        $image = trim((string) $this->image);

        if ($image === '') {
            return null;
        }

        if (str_starts_with($image, 'http')
            && ! str_contains($image, '/ecommerce-products/')
            && ! str_contains($image, '/ecommerce-categories/')) {
            return $image;
        }

        $path = parse_url($image, PHP_URL_PATH) ?: $image;
        $filename = basename(str_replace('\\', '/', $path));

        return asset('uploads/ecommerce-categories/' . $filename);
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'discount_ecommerce_categories', 'ecommerce_product_category_id', 'discount_id');
    }
}
