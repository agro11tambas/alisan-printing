<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('uploads/' . $this->image) : null;
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'discount_ecommerce_categories', 'ecommerce_product_category_id', 'discount_id');
    }
}
