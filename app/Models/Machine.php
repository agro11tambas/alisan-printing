<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'machines';

    protected $fillable = [
        'name',
        'active',
    ];

    public function assigns()
    {
        return $this->hasMany(OrderProgressAssign::class, 'machine_id', 'id');
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'machine_id', 'id');
    }
}
