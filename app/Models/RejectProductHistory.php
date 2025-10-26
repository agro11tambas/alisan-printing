<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectProductHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reject_product_histories';

    protected $fillable = [
        'reject_product_id',
        'product_id',
        'inventory_warehouse_id',
        'quantity',
        'date',
        'status',
        'note',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function rejectProduct()
    {
        return $this->belongsTo(RejectProduct::class, 'reject_product_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
