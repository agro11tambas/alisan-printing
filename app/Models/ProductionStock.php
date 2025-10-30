<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductionStock extends Model
{
    use HasFactory;

    protected $table = 'production_stocks';

    protected $fillable = [
        'production_warehouse_id',
        'product_id',
        'opening_stock',
        'opening_finished_product_stock',
        'finished_product_stock',
        'canceled_product_stock',
        'available_quantity',
        'incoming_stock',
    ];

    public function warehouse()
    {
        return $this->belongsTo(ProductionWarehouse::class, 'production_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function orderProgressItems()
    {
        return $this->hasMany(OrderProgressItem::class, 'product_id', 'product_id');
    }

    public function canceledProducts()
    {
        return $this->hasMany(CanceledProduct::class, 'production_stock_id');
    }

    public function designItems()
    {
        return $this->hasMany(DesignItem::class, 'product_id', 'product_id');
    }

    public function getRemainingQuantityAttribute()
    {
        $totalDesignQty = $this->designItems()
            ->select(DB::raw('COALESCE(SUM(quantity), 0) as total'))
            ->value('total') ?? 0;

        $totalCompletedQty = $this->orderProgressItems()
            ->select(DB::raw('COALESCE(SUM(completed_quantity), 0) as total'))
            ->value('total') ?? 0;

        return max($totalDesignQty - $totalCompletedQty, 0);
    }
}
