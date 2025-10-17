<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItemComponent extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'order_item_id',
        'product_id',
        'qty',
        'avg_cost_at_sale',
        'total_cost',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    protected static function booted()
    {
        static::restoring(function ($component) {
            // Pastikan parent order_item ikut direstore kalau masih terhapus
            if ($component->orderItem && $component->orderItem->trashed()) {
                $component->orderItem->restore();
            }
        });
    }
}
