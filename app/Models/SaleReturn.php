<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturn extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'sale_returns';

    protected $fillable = [
        'status_edited',
        'sale_order_id',
        'customer_id',
        'order_number',
        'return_date',
        'payment_status',
        'total_amount',
        'account',
        'status',
        'refund_amount',
        'remaining_amount',
        'return_address',
        'google_map',
        'return_image',
        'note',
        'reason',
        'transaction_group_id',
        'deleted_by',
        'deleted_notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relasi ke Order (sale order)
    public function saleOrder()
    {
        return $this->belongsTo(Order::class, 'sale_order_id');
    }

    // Relasi ke Customer
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    // Relasi ke akun kas/bank
    public function cashBankAccount()
    {
        return $this->belongsTo(Account::class, 'cash_bank_account_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'sale_return_id', 'id');
    }

    // Relasi ke SaleReturnItems
    public function items()
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_return_id')->withTrashed();
    }

    public function editHistories()
    {
        return $this->hasMany(SaleReturnEditHistory::class, 'sale_return_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function canceledProducts()
    {
        return $this->hasMany(CanceledProduct::class, 'sale_return_id');
    }

    protected static function booted()
    {
        static::deleting(function ($saleReturn) {
            if ($saleReturn->isForceDeleting()) {
                $saleReturn->items()->withTrashed()->forceDelete();
                $saleReturn->editHistories()->withTrashed()->forceDelete();
                $saleReturn->canceledProducts()->withTrashed()->forceDelete();
            } else {
                $saleReturn->items()->delete();
                $saleReturn->editHistories()->delete();
                $saleReturn->canceledProducts()->delete();
            }
        });

        static::restoring(function ($saleReturn) {
            $saleReturn->items()->withTrashed()->restore();
            $saleReturn->editHistories()->withTrashed()->restore();
            $saleReturn->canceledProducts()->withTrashed()->restore();
        });
    }
}
