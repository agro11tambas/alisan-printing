<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningBalance extends Model
{
    use HasFactory;

    protected $table = 'opening_balances';

    protected $fillable = [
        'transaction_date',
        'amount',
        'account',
        'description',
    ];
}
