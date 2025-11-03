<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefectProductHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'defect_product_histories';

    protected $fillable = [
        'defect_product_id',
        'product_id',
        'supplier_id',
        'quantity',
        'action_type',
        'note',
        'action_date',
        'user_id',
    ];

    protected $casts = [
        'action_date' => 'date',
        'deleted_at'  => 'datetime',
    ];

    public function defectProduct()
    {
        return $this->belongsTo(DefectProduct::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
