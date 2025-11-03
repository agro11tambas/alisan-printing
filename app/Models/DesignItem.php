<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'design_id',
        'order_item_id',
        'product_id',
        'quantity',
        'completed_quantity',
        'design_file',
        'preview_image',
        'verification_status',
        'verified_by',
        'verified_at',
        'note',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function design()
    {
        return $this->belongsTo(Design::class, 'design_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }
}
