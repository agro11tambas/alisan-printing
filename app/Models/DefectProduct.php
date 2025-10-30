<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefectProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'defect_products';

    protected $fillable = [
        'product_id',
        'purchase_id',
        'purchase_return_id',
        'supplier_id',
        'inventory_id',
        'inventory_stock_out_id',
        'inventory_item_id',
        'sale_return_id',
        'sale_return_item_id',
        'defect_date',
        'quantity',
        'eliminated_quantity',
        'returned_quantity',
        'defect_type',
        'status',
        'note',
        'defect_image',
        'user_id',
    ];

    protected $casts = [
        'defect_date' => 'date',
        'deleted_at'  => 'datetime',
    ];

    // 🔗 RELASI

    public function product()
    {
        return $this->belongsTo(Products::class)->withTrashed();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function stockOut()
    {
        return $this->belongsTo(InventoryStockOut::class, 'inventory_stock_out_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function histories()
    {
        return $this->hasMany(DefectProductHistory::class, 'defect_product_id');
    }
}
