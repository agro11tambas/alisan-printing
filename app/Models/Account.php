<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'closing_balance',
        'is_default',
    ];

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function openingBalance()
    {
        return $this->hasMany(ManageOpeningBalance::class);
    }

    public function accountClosingBalance()
    {
        $debit = $this->transactions()->sum('debit');
        $credit = $this->transactions()->sum('credit');

        $opening = $this->opening_balance;
        $closing = 0;

        switch($this->name) {
            case 'Bank':
                $closing = $opening + $debit - $credit;
                break;
            case 'Expense':
                $closing = $opening + $debit;
                break;
            case 'Sale':
                $closing = $opening + $credit - $debit;
                break;
            case 'Purchase':
                $closing = $opening - $debit - $credit;
                break;
            case 'Cash':
                $closing = $opening + $debit - $credit;
                break;
            case 'Capital':
                $closing = $opening + $debit - $credit;
                break;
        }

        $this->closing_balance = $closing;
        $this->save();
    }

    public function accountOpeningBalance()
    {
        $debit = $this->openingBalance()->sum('debit');
        $credit = $this->openingBalance()->sum('credit');

        $opening = 0;

        switch($this->name) {
            case 'Bank':
                $opening = $debit - $credit;
                break;
            case 'Expense':
                $opening = $debit - $credit;
                break;
            case 'Sale':
                $opening = $credit - $debit;
                break;
            case 'Purchase':
                $opening = - $debit - $credit;
                break;
            case 'Cash':
                $opening = $debit - $credit;
                break;
            case 'Capital':
                $opening = $debit - $credit;
                break;
        }

        $this->opening_balance = $opening;
        $this->save();
    }
}
