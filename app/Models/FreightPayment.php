<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreightPayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'freight_payments';

    protected $fillable = [
        'waybill_key',
        'waybill_number',
        'supplier_id',
        'paid_amount',
        'transaction_date',
        'cash_bank_account_id',
        'purchase_account_id',
        'transaction_group_id',
        'proof',
        'note',
        'particular',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'paid_amount' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function cashBankAccount()
    {
        return $this->belongsTo(Account::class, 'cash_bank_account_id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
