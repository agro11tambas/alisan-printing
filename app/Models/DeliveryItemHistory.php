<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryItemHistory extends Model
{
    protected $table = 'delivery_item_histories';

    protected $fillable = [
        'delivery_id',
        'order_item_id',
        'delivered_quantity',
        'note',
        'kurir',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
