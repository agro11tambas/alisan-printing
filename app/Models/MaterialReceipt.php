<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialReceipt extends Model
{
    use HasFactory;

    protected $table = 'material_receipts';

    protected $fillable = [
        'material_request_id',
        'received_by',
        'received_at',
        'status',
        'note',
    ];

    // Relasi ke header request
    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    // Relasi ke item-item penerimaan
    public function items()
    {
        return $this->hasMany(MaterialReceiptItem::class);
    }
}
