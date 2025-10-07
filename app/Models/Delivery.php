<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'deliveries';

    protected $fillable = [
        'order_id',
        'user_id',
        'invoice_number',
        'delivered_by',
        'delivery_proof',
        'notes',
        'delivered_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItemHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
