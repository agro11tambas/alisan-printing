<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerAddresses;

class Customers extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'woocommerce_customer_id',
    ];

    public function addresses()
    {
        return $this->hasMany(CustomerAddresses::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
