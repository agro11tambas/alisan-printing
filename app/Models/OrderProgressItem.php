<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'order_progress_items';

    protected $fillable = [
        'order_progress_id',
        'design_item_id',
        'order_item_id',
        'product_id',
        'quantity',
        'completed_quantity',
        'operator_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function progress()
    {
        return $this->belongsTo(OrderProgress::class, 'order_progress_id');
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'order_progress_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function operators()
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'id');
    }

    public function deliveryOrderItems()
    {
        return $this->hasMany(DeliveryOrderItem::class, 'order_progress_item_id');
    }

    public function assigns()
    {
        return $this->hasMany(OrderProgressAssign::class, 'order_progress_item_id');
    }
}
