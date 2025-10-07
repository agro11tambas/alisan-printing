<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStockInHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'inventory_stock_in_histories_2';

    protected $fillable = [
        'inventory_stock_in_id',
        'inventory_item_id',
        'stock_in',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function stockIn()
    {
        return $this->belongsTo(InventoryStockIn::class, 'inventory_stock_in_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function product()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchase_product_id');
    }
}
