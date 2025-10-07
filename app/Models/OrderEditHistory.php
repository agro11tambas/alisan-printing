<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderEditHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'order_edit_histories';

    protected $fillable = [
        'order_id',
        'edited_by',
        'changes',
        'text',
        'edited_at',
    ];

    protected $casts = [
        'changes'   => 'array',      // otomatis decode JSON jadi array
        'edited_at' => 'datetime',   // supaya bisa format pakai ->format()
        'deleted_at' => 'datetime',
    ];

    // 🔹 Relasi ke Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔹 Relasi ke User (siapa yang edit)
    public function user()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
