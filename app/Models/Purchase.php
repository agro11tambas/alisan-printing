<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchases';

    protected $fillable = [
        'status_edited',
        'purchase_number',
        'purchase_date',
        'due_date',
        'name',
        'sub_total',
        'tax_percent',
        'tax_amount',
        'total_amount',
        'notes',
        'payment_status',
        'payment_method',
        'paid_amount',
        'remaining_amount',
        'total_amount_product',
        'paid_amount_product',
        'remaining_amount_product',
        'total_amount_freight',
        'paid_amount_freight',
        'remaining_amount_freight',
        'status',
        'image',
        'supplier_id',
        'transaction_group_id',
        'transaction_type',
        'deleted_by',
        'deleted_notes',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'due_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function purchaseReturn()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'purchase_id', 'id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'transaction_type');
    }

    public function purchaseEditHistories()
    {
        return $this->hasMany(PurchaseEditHistory::class, 'purchase_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'purchase_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'purchase_id');
    }

    public function hasStockIn()
    {
        return $this->inventories()
            ->where('status', 'Stock In')
            ->whereHas('items', function ($q) {
                $q->where('stock_in', '>', 0);
            })
            ->exists();
    }
    public function getIsFullyReturnedAttribute()
    {
        // ambil total qty purchase
        $totalPurchased = $this->purchaseItems()->sum('quantity');

        // ambil total qty yang sudah direturn
        $totalReturned = \App\Models\PurchaseReturnItem::whereHas('purchaseReturn', function ($q) {
            $q->where('purchase_id', $this->id);
        })->sum('quantity');

        return $totalPurchased > 0 && $totalPurchased <= $totalReturned;
    }


    protected static function booted()
    {
        static::deleting(function ($purchase) {
            if ($purchase->isForceDeleting()) {
                $purchase->purchaseItems()->forceDelete();
                $purchase->purchaseReturn()->forceDelete();
                $purchase->purchaseEditHistories()->forceDelete();
                $purchase->accountTransactions()->forceDelete();
                $purchase->inventories()->forceDelete();
                $purchase->defectProducts()->forceDelete();
            } else {
                $purchase->purchaseItems()->delete();
                $purchase->purchaseReturn()->delete();
                $purchase->purchaseEditHistories()->delete();
                $purchase->accountTransactions()->delete();
                $purchase->inventories()->delete();
                $purchase->defectProducts()->delete();
            }
        });

        static::restoring(function ($purchase) {
            $purchase->purchaseItems()->withTrashed()->restore();
            $purchase->purchaseReturn()->withTrashed()->restore();
            $purchase->purchaseEditHistories()->withTrashed()->restore();
            $purchase->accountTransactions()->withTrashed()->restore();
            $purchase->inventories()->withTrashed()->restore();
            $purchase->defectProducts()->withTrashed()->restore();
        });
    }
}
