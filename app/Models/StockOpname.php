<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'product_id',
        'inventory_warehouse_id',
        'date',
        'quantity',
        'status',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
