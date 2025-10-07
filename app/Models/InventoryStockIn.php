<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStockIn extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'inventory_stock_ins_2';

    protected $fillable = [
        'inventory_id',
        'user_id',
        'invoice_number',
        'purchase_id',
        'production_stock_id',
        'change_date',
        'waybill_number',
        'waybill_image',
        'status',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function histories()
    {
        return $this->hasMany(InventoryStockInHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
