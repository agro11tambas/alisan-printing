<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameProduction extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_productions';

    protected $fillable = [
        'product_id',
        'production_warehouse_id',
        'date',
        'change',
        'finished_product',
        'available_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
