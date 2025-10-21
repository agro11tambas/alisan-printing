<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $guarded = [];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'supplier_id');
    }
}
