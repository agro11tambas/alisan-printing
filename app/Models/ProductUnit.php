<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function productUnits()
    {
        return $this->hasMany(ProductUnitConversion::class, 'unit_id');
    }

    public function bundleUnits()
    {
        return $this->hasMany(ProductBundleUnitConversion::class, 'unit_id');
    }
}
