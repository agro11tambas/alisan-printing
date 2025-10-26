<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressAssignBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_progress_assign_batches';

    protected $fillable = [
        'order_progress_id',
        'assign_code',
        'assign_date',
        'note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function orderProgress()
    {
        return $this->belongsTo(OrderProgress::class, 'order_progress_id');
    }

    public function assigns()
    {
        return $this->hasMany(OrderProgressAssign::class, 'assign_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
