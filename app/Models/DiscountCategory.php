<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCategory extends Model
{
    protected $table = 'discount_categories';

    protected $fillable = [
        'category_id',
        'discount_id',
    ];
}
