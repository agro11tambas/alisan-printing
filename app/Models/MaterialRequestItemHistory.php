<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialRequestItemHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'material_request_item_histories';

    protected $fillable = [
        'material_request_item_id',
        'quantity',
        'status',
        'date',
        'note',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function materialRequestItem()
    {
        return $this->belongsTo(MaterialRequestItem::class);
    }
}
