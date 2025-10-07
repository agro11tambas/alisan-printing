<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'inventory_warehouse_id',
        'product_id',
        'opening_stock',
        'opening_rate',
        'minimum_stock',
        'inventory_stock',
        'incoming_stock',
        'stock_after_sales',
        'avg_cost',
        'available_quantity',
        'created_at',
        'updated_at',
    ];

    public function warehouse()
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }
}
