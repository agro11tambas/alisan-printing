<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressAssign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_progress_assigns';

    protected $fillable = [
        'assign_batch_id',
        'assign_code',
        'order_progress_item_id',
        'operator_id',
        'date',
        'assigned_quantity',
        'change_quantity',
        'completed_quantity',
        'defect_quantity',
        'reject_quantity',
        'note',
        'status',
    ];
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(OrderProgressAssignBatch::class, 'assign_batch_id');
    }

    public function progressItem()
    {
        return $this->belongsTo(OrderProgressItem::class, 'order_progress_item_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'order_progress_assign_id', 'id');
    }

    protected static function booted()
    {
        static::deleting(function ($assign) {
            if ($assign->isForceDeleting()) {
                $assign->histories()->withTrashed()->get()->each->forceDelete();
            } else {
                $assign->histories()->get()->each->delete();
            }
        });

        static::restoring(function ($assign) {
            $assign->histories()->withTrashed()->get()->each->restore();
        });
    }
}
