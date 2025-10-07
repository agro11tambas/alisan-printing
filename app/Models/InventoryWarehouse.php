<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryWarehouse extends Model
{
    use HasFactory;

    protected $table = 'inventory_warehouses';

    protected $fillable = [
        'name',
        'location',
    ];

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'inventory_warehouse_id');
    }
}
