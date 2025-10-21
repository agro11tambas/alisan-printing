<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'status_edited',
        'purchase_id',
        'supplier_id',
        'purchase_number',
        'return_date',
        'status',
        'account',
        'payment_status',
        'total_amount',
        'refund_amount',
        'remaining_amount',
        'total_amount_product',
        'paid_amount_product',
        'remaining_amount_product',
        'total_amount_freight',
        'paid_amount_freight',
        'remaining_amount_freight',
        'payment_status',
        'transaction_group_id',
        'note',
        'return_type',
        'reason',
        'transaction_type',
        'return_image',
        'deleted_by',
        'deleted_notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }

    public function cashBankAccount()
    {
        return $this->belongsTo(Account::class, 'cash_bank_account_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'purchase_return_id', 'id');
    }

    public function editHistories()
    {
        return $this->hasMany(PurchaseReturnEditHistory::class, 'purchase_return_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'purchase_return_id')->withTrashed();
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'purchase_return_id');
    }

    public function hasStockOut()
    {
        return $this->inventories()
            ->where('status', 'Stock Out')
            ->whereHas('items', function ($q) {
                $q->where('stock_out', '>', 0);
            })
            ->exists();
    }

    protected static function booted()
    {
        static::deleting(function ($return) {
            if ($return->isForceDeleting()) {
                $return->items()->forceDelete();
                $return->editHistories()->forceDelete();
                $return->accountTransactions()->forceDelete();
                $return->inventories()->forceDelete();
            } else {
                $return->items()->delete();
                $return->editHistories()->delete();
                $return->accountTransactions()->delete();
                $return->inventories()->delete();
            }
        });

        static::restoring(function ($return) {
            $return->items()->withTrashed()->restore();
            $return->editHistories()->withTrashed()->restore();
            $return->accountTransactions()->withTrashed()->restore();
            $return->inventories()->withTrashed()->restore();
        });
    }
}
