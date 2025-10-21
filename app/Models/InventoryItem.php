<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'inventory_items_2';

    protected $fillable = [
        'inventory_id',
        'product_id',
        'inventory_warehouse_id',
        'order_item_id',
        'product_bundle_id',
        'purchase_item_id',
        'purchase_return_item_id',
        'material_request_item_id',
        'material_receipt_item_id',
        'quantity',
        'price',
        'stock_in',
        'remaining_stock_in',
        'stock_out',
        'stock_out_request',
        'stock_out_production',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function inventoryStockOut()
    {
        return $this->hasMany(InventoryStockOutHistory::class, 'inventory_item_id');
    }

    public function inventoryStockIn()
    {
        return $this->hasMany(InventoryStockInHistory::class, 'inventory_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function productBundle()
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function purchaseReturnItem()
    {
        return $this->belongsTo(PurchaseReturnItem::class, 'purchase_return_item_id');
    }

    public function materialRequestItem()
    {
        return $this->belongsTo(MaterialRequestItem::class, 'material_request_item_id');
    }

    public function materialReceiptItem()
    {
        return $this->belongsTo(MaterialReceiptItem::class, 'material_receipt_item_id');
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'inventory_item_id');
    }
}
