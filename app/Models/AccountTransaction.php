<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'account_transactions';

    protected $fillable = [
        'order_id',
        'sale_return_id',
        'purchase_id',
        'purchase_return_id',
        'customer_id',
        'order_number',
        'purchase_number',
        'account_id',
        'transaction_date',
        'credit',
        'debit',
        'balance',
        'note',
        'proof',
        'verified',
        'particular',
        'transaction_group_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
