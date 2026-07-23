<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_account_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class);
    }
}
