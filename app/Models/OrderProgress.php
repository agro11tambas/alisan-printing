<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgress extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'order_progresses_2';

    protected $fillable = [
        'order_id',
        'design_id',
        'invoice_number',
        'status',
        'date',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(OrderProgressItem::class, 'order_progress_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class)->withTrashed();
    // }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'production_id');
    }

    public function batches()
    {
        return $this->hasMany(OrderProgressBatch::class, 'order_progress_id');
    }

    public function assignBatches()
    {
        return $this->hasMany(OrderProgressAssignBatch::class, 'order_progress_id');
    }

    public function rejectProducts()
    {
        return $this->hasMany(RejectProduct::class, 'order_progress_id');
    }

    protected static function booted()
    {
        static::deleting(function ($progress) {
            if ($progress->isForceDeleting()) {
                $progress->items()->get()->each->forceDelete();
                // $progress->assigns()->delete();
                $progress->assignBatches()->get()->each->forceDelete();
                $progress->batches()->get()->each->forceDelete();
            } else {
                $progress->items()->get()->each->delete();
                // $progress->assigns()->delete();
                $progress->assignBatches()->get()->each->delete();
                $progress->batches()->get()->each->delete();
            }
        });

        static::restoring(function ($progress) {
            $progress->items()->get()->each->restore();
            // $progress->assigns()->restore();
            $progress->assignBatches()->get()->each->restore();
            $progress->batches()->get()->each->restore();
        });
    }
}
