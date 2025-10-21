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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'production_id');
    }

    public function batches()
    {
        return $this->hasMany(OrderProgressBatch::class, 'order_progress_id');
    }

    protected static function booted()
    {
        static::deleting(function ($progress) {
            if ($progress->isForceDeleting()) {
                $progress->items()->withTrashed()->get()->each->forceDelete();
                $progress->batches()->withTrashed()->get()->each->forceDelete();
            } else {
                $progress->items()->get()->each->delete();
                $progress->batches()->get()->each->delete();
            }
        });

        static::restoring(function ($progress) {
            $progress->items()->withTrashed()->get()->each->restore();
            $progress->batches()->withTrashed()->get()->each->restore();
        });
    }
}
