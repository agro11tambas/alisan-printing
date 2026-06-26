<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerAddresses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// class Customers extends Model
// {
//     use HasFactory, SoftDeletes;

//     protected $table = 'customers';

//     protected $fillable = [
//         'name',
//         'phone',
//         'customer_deposit',
//         'user_id',
//     ];

//     protected $casts = [
//         'deleted_at' => 'datetime',
//     ];

//     public function addresses()
//     {
//         return $this->hasMany(CustomerAddresses::class, 'customer_id');
//     }

//     public function orders(): HasMany
//     {
//         return $this->hasMany(Order::class);
//     }

//     public function user()
//     {
//         return $this->belongsTo(User::class);
//     }

//     public function accounts()
//     {
//         return $this->hasMany(CustomerAccount::class, 'customer_id');
//     }
// }

class Customers extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'customer_deposit',
        'user_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddresses::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accounts()
    {
        return $this->belongsToMany(
            CustomerAccount::class,
            'customer_account_customer',
            'customer_id',
            'customer_account_id'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::deleting(function ($customer) {
            if (! $customer->isForceDeleting()) {
                $customer->addresses()->delete();
            }
        });

        static::restoring(function ($customer) {
            $customer->addresses()->withTrashed()->restore();
        });

        static::forceDeleted(function ($customer) {
            $customer->addresses()->withTrashed()->forceDelete();
        });
    }
}
