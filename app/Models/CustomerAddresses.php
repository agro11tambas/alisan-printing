<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customers;

class CustomerAddresses extends Model
{
    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id', 'address', 'google_maps'  
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
}
