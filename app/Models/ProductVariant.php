<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
    ];

    public function options()
    {
        return $this->hasMany(ProductVariantOption::class);
    }
}
