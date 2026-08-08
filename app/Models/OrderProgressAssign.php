<?php

namespace App\Models;

use Carbon\Carbon;
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
        'product_id',
        'operator_id',
        'date',
        'assigned_quantity',
        'change_quantity',
        'completed_quantity',
        'defect_quantity',
        'reject_quantity',
        'note',
        'status',

        'product_unit_conversion_id',
        'unit_name',
        'unit_conversion_value',
    ];
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

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
        return $this->belongsTo(Operator::class, 'operator_id')->withTrashed();
    }

    public function histories()
    {
        return $this->hasMany(OrderProgressHistory::class, 'order_progress_assign_id', 'id');
    }

    protected static function booted()
    {
        static::created(function (OrderProgressAssign $assign) {
            ProductionStockSnapshot::adjustAssign(
                (int) $assign->product_id,
                $assign->snapshotDate(),
                (int) $assign->assigned_quantity
            );
        });

        static::updated(function (OrderProgressAssign $assign) {
            // restore() ikut lewat sini; sudah ditangani oleh event restored
            if ($assign->wasChanged('deleted_at')) {
                return;
            }

            if (! $assign->wasChanged(['product_id', 'assigned_quantity'])) {
                return;
            }

            ProductionStockSnapshot::adjustAssign(
                (int) $assign->getOriginal('product_id'),
                $assign->snapshotDate(true),
                -(int) $assign->getOriginal('assigned_quantity')
            );

            ProductionStockSnapshot::adjustAssign(
                (int) $assign->product_id,
                $assign->snapshotDate(),
                (int) $assign->assigned_quantity
            );
        });

        static::deleting(function ($assign) {
            // force delete atas record yang sudah soft-deleted: sudah dikurangi saat soft delete
            if (! $assign->trashed()) {
                ProductionStockSnapshot::adjustAssign(
                    (int) $assign->product_id,
                    $assign->snapshotDate(),
                    -(int) $assign->assigned_quantity
                );
            }

            if ($assign->isForceDeleting()) {
                $assign->histories()->withTrashed()->get()->each->forceDelete();
            } else {
                $assign->histories()->get()->each->delete();
            }
        });

        static::restored(function (OrderProgressAssign $assign) {
            ProductionStockSnapshot::adjustAssign(
                (int) $assign->product_id,
                $assign->snapshotDate(),
                (int) $assign->assigned_quantity
            );
        });

        static::restoring(function ($assign) {
            $assign->histories()->withTrashed()->get()->each->restore();
        });
    }

    /**
     * Tanggal snapshot yang dipakai untuk assign_today — harus sama dengan
     * dasar perhitungan command stock:snapshot, yaitu DATE(created_at).
     */
    public function snapshotDate(bool $original = false): string
    {
        $createdAt = $original ? $this->getOriginal('created_at') : $this->created_at;

        return ($createdAt ? Carbon::parse($createdAt) : now())->toDateString();
    }
}
