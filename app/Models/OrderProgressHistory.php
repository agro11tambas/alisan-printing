<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProgressHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'order_progress_histories_2';

    protected $fillable = [
        'order_progress_batch_id',
        'order_progress_item_id',
        'order_progress_assign_id',
        'change_quantity',
        'completed_quantity',
        'defect_quantity',
        'reject_quantity',
        'operator_id',
        'note'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(OrderProgressBatch::class, 'order_progress_batch_id');
    }

    public function progressItem()
    {
        return $this->belongsTo(OrderProgressItem::class, 'order_progress_item_id');
    }

    public function operators()
    {
        return $this->belongsTo(Operator::class, 'operator_id', 'id')->withTrashed();
    }

    public function assign()
    {
        return $this->belongsTo(OrderProgressAssign::class, 'order_progress_assign_id');
    }
}
