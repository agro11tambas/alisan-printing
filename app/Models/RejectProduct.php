<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reject_products';

    protected $fillable = [
        'product_id',
        'order_progress_id',
        'order_progress_batch_id',
        'reject_date',
        'quantity',
        'eliminated_quantity',
        'returned_quantity',
        'status',
        'note',
        'user_id',
        'order_progress_history_2_id',
    ];

    protected $casts = [
        'reject_date' => 'date',
        'deleted_at'  => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function orderProgress()
    {
        return $this->belongsTo(OrderProgress::class, 'order_progress_id');
    }

    public function orderProgressBatch()
    {
        return $this->belongsTo(OrderProgressBatch::class, 'order_progress_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function histories()
    {
        return $this->hasMany(RejectProductHistory::class, 'reject_product_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'reject_product_id');
    }

    public function orderProgressHistory2()
    {
        return $this->belongsTo(OrderProgressHistory::class, 'order_progress_history_2_id');
    }


    // public function warehouses()
    // {
    //     return $this->hasManyThrough(
    //         InventoryWarehouse::class,
    //         RejectProductHistory::class,
    //         'reject_product_id',
    //         'id',
    //         'id',
    //         'inventory_warehouse_id'
    //     );
    // }
}
