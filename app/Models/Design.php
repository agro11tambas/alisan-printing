<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Design extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'designs';

    protected $fillable = [
        'order_id',
        'design_number',
        'date',
        'status',
        'status_edited',
        'notes',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(DesignItem::class, 'design_id');
    }
}
