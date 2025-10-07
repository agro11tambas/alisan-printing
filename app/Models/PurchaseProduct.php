<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PurchaseItem;

class PurchaseProduct extends Model
{
    use HasFactory;

    protected $table = 'purchase_products';

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'stock',
        'inventory_stock',
        'stock_after_sales',
        'avg_cost',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_product_id');
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_product_id');
    }

    public function inventoryItem()
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function stockOutHistories()
    {
        return $this->hasMany(InventoryStockOutHistory::class);
    }
}
