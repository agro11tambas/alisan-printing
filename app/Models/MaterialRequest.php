<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequest extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'material_requests';

    protected $fillable = [
        'requested_by',
        'requested_at',
        'status',
        'warehouse_status',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class)->withTrashed();;
    }

    public function receipt()
    {
        return $this->hasOne(MaterialReceipt::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function hasStockOut(): bool
    {
        return InventoryItem::whereHas('inventory', function ($q) {
            $q->where('material_request_id', $this->id);
        })->where('stock_out', '>', 0)->exists();
    }

    protected static function booted()
    {
        static::deleting(function ($materialRequest) {
            if ($materialRequest->isForceDeleting()) {
                $materialRequest->items()->forceDelete();
            } else {
                $materialRequest->items()->delete();
            }
        });

        static::restoring(function ($materialRequest) {
            $materialRequest->items()->withTrashed()->restore();
        });
    }
}
