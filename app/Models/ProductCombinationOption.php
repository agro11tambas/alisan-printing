<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCombinationOption extends Model
{
    protected $table = 'product_combination_options';

    protected $fillable = [
        'product_combination_id',
        'product_variant_option_id',
    ];
}
