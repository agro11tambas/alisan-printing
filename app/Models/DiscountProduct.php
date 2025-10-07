<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Products;
use App\Models\Discount;

class DiscountProduct extends Model
{
    protected $table = 'discount_products';

    protected $fillable = [
        'product_id',
        'discount_id',
    ];
}
