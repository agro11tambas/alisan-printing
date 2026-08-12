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
        'machine_id',
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

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    protected static function booted()
    {
        static::deleting(function ($batch) {
            // dihapus per-model, bukan mass delete, supaya event di
            // OrderProgressAssign (histories + assign_today) tetap jalan
            if ($batch->isForceDeleting()) {
                $batch->assigns()->withTrashed()->get()->each->forceDelete();
            } else {
                $batch->assigns()->get()->each->delete();
            }
        });

        static::restoring(function ($batch) {
            $batch->assigns()->withTrashed()->get()->each->restore();
        });
    }
}
