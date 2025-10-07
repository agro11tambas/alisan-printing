<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionWarehouse extends Model
{
    use HasFactory;

    protected $table = 'production_warehouses';

    protected $fillable = [
        'name',
        'location',
    ];

    public function stocks()
    {
        return $this->hasMany(ProductionStock::class, 'production_warehouse_id');
    }
}
