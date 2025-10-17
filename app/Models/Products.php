<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\Discount;
use Carbon\Carbon;

class Products extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'sku',
        'stock',
        'image',
        'gallery',
        'short_description',
        'sale_price',
        'purchase_stock',
        'inventory_stock',
        'stock_after_sales',
        'avg_cost',
        'opening_stock',
        'opening_rate',
    ];

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'discount_products', 'product_id', 'discount_id');
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_product', 'product_id', 'category_id')->with('discounts');
    }

    public function tags()
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_product', 'product_id', 'tag_id');
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItemHistory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class, 'product_id');
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'product_id');
    }

    public function bundleItems()
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function productionStocks()
    {
        return $this->hasOne(ProductionStock::class, 'product_id');
    }

    public function includedInBundles()
    {
        return $this->hasMany(ProductBundleItem::class, 'product_id');
    }

    public function inventoryStock()
    {
        return $this->hasOne(InventoryStock::class, 'product_id');
    }

    public function getApplicableDiscount()
    {
        $now = Carbon::now();

        $discount = $this->discounts()
            ->where('is_active', 1)
            ->first();

        if (!$discount) {
            foreach ($this->categories as $category) {
                $discount = $category->discounts()
                    ->where('is_active', 1)
                    ->first();

                if ($discount) break;
            }
        }

        return $discount;
    }
}
