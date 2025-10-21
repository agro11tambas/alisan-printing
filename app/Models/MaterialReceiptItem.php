<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class MaterialReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'material_receipt_items';

    protected $fillable = [
        'material_receipt_id',
        'material_request_item_id',
        'product_id',
        'received_qty',
        'note',
    ];

    // Relasi ke header receipt
    public function materialReceipt()
    {
        return $this->belongsTo(MaterialReceipt::class);
    }

    // Relasi ke request item
    public function materialRequestItem()
    {
        return $this->belongsTo(MaterialRequestItem::class);
    }

    // Relasi ke produk
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }
}
