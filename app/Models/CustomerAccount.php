<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

// class CustomerAccount extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

//     protected $table = 'customer_accounts';

//     protected $fillable = [
//         'customer_id',
//         'google_id',
//         'name',
//         'email',
//         'avatar',
//         'whatsapp_number',
//         'password',
//         'auth_provider',
//         'is_active',
//         'last_login_at',
//     ];

//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     protected function casts(): array
//     {
//         return [
//             'is_active' => 'boolean',
//             'last_login_at' => 'datetime',
//             'deleted_at' => 'datetime',
//         ];
//     }

//     public function customer()
//     {
//         return $this->belongsTo(Customers::class, 'customer_id');
//     }
// }

class CustomerAccount extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customer_accounts';

    protected $fillable = [
        'customer_id',
        'google_id',
        'name',
        'email',
        'avatar',
        'whatsapp_number',
        'password',
        'auth_provider',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function customers()
    {
        return $this->belongsToMany(
            Customers::class,
            'customer_account_customer',
            'customer_account_id',
            'customer_id'
        )->withTimestamps();
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CustomerCartItem::class);
    }

    public function passwordResetToken()
    {
        return $this->hasOne(CustomerPasswordResetToken::class);
    }

    public function getPasswordResetStatusAttribute(): string
    {
        $resetToken = $this->passwordResetToken;

        if (! $resetToken) {
            return 'not_created';
        }

        if ($resetToken->used_at) {
            return 'completed';
        }

        if ($resetToken->expires_at->isPast()) {
            return 'expired';
        }

        return 'pending';
    }
}
