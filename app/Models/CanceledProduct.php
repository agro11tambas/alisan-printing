<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CanceledProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'canceled_products';

    protected $fillable = [
        'production_stock_id',
        'product_id',
        'warehouse_id',
        'sale_return_id',
        'sale_return_item_id',
        'order_id',
        'order_item_id',
        'quantity',
        'completed_quantity',
        'avg_cost_at_cancel',
        'fixed_cost_at_cancel',
        'total_cost',
        'total_fixed_cost',
        'date',
        'type',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductionWarehouse::class, 'warehouse_id');
    }

    public function stock()
    {
        return $this->belongsTo(ProductionStock::class, 'production_stock_id');
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function saleReturnItem()
    {
        return $this->belongsTo(SaleReturnItem::class, 'sale_return_item_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
