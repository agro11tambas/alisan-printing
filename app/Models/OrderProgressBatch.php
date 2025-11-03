<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressBatch extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'order_progress_batches';

    protected $fillable = [
        'order_progress_id',
        'user_id',
        'date',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function orderProgress()
    {
        return $this->belongsTo(OrderProgress::class, 'order_progress_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'order_progress_batch_id', 'id');
    }

    public function rejectProducts()
    {
        return $this->hasMany(RejectProduct::class, 'order_progress_batch_id');
    }

    protected static function booted()
    {
        static::deleting(function ($batch) {
            if ($batch->isForceDeleting()) {
                $batch->histories()->forceDelete();
            } else {
                $batch->histories()->delete();
            }
        });

        static::restoring(function ($batch) {
            $batch->histories()->withTrashed()->restore();
        });
    }
}
