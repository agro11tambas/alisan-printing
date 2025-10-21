<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanceledProductHistory extends Model
{
    use HasFactory;

    protected $table = 'canceled_product_histories';

    protected $fillable = [
        'production_stock_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date', // atau 'datetime' kalau pakai jam juga
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

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
