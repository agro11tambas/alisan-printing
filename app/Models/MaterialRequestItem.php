<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequestItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'material_request_items';

    protected $fillable = [
        'material_request_id',
        'product_id',
        'requested_qty',
        'issued_qty',
        'received_qty',
        'unit',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    // Relasi ke stock out
    public function stockOuts()
    {
        return $this->hasMany(InventoryStockOut::class, 'material_request_item_id');
    }

    // Relasi ke material receipt item
    public function receiptItem()
    {
        return $this->hasOne(MaterialReceiptItem::class);
    }

    public function histories()
    {
        return $this->hasMany(MaterialRequestItemHistory::class);
    }
}
