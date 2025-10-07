<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageOpeningBalance extends Model
{
    use HasFactory;

    protected $table = 'manage_opening_balances';

    protected $fillable = [
        'account_id',
        'credit',
        'debit',
        'balance',
        'transaction_date',
        'note',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
