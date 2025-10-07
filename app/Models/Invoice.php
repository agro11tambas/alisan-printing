<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    protected $table = 'invoices';

    protected $fillable = [
        'bank_name',
        'account_number',
        'name',
        'phone',
        'address',
        'terms_and_conditions',
    ];

    public function termAndConditions()
    {
        return $this->hasMany(TermAndCondition::class);
    }
}
