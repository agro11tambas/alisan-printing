<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcommerceInformation extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_information';

    protected $fillable = [
        'phone_number',
    ];
}
