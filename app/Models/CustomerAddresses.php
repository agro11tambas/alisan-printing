<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// class CustomerAddresses extends Model
// {
//     use HasFactory, SoftDeletes;

//     protected $table = 'customer_addresses';

//     protected $fillable = [
//         'customer_id',
//         'business_name',
//         'address',
//         'google_maps'
//     ];

//     protected $casts = [
//         'deleted_at' => 'datetime',
//     ];

//     public function customer()
//     {
//         return $this->belongsTo(Customers::class, 'customer_id')->withTrashed();
//     }

//     public function orders()
//     {
//         return $this->hasMany(Order::class, 'customer_address_id');
//     }

//     protected static function booted(): void
//     {
//         static::deleting(function ($customer) {
//             if (! $customer->isForceDeleting()) {
//                 $customer->addresses()->delete(); // soft delete semua alamat
//             }
//         });

//         static::restoring(function ($customer) {
//             $customer->addresses()->withTrashed()->restore(); // restore alamat
//         });

//         static::forceDeleted(function ($customer) {
//             $customer->addresses()->withTrashed()->forceDelete(); // hard delete alamat
//         });
//     }
// }

class CustomerAddresses extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id',
        'business_name',
        'address',
        'google_maps',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id')->withTrashed();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_address_id');
    }
}
