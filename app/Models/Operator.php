<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operators';

    protected $fillable = [
        'name',
        'active',
    ];

    public function progressItems()
    {
        return $this->hasMany(OrderProgressItem::class, 'operator_id', 'id');
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'operator_id', 'id');
    }
}
