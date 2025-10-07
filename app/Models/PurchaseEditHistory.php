<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseEditHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchase_edit_histories';

    protected $fillable = [
        'purchase_id',
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

    // 🔹 Relasi ke Purchase

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // 🔹 Relasi ke User (siapa yang edit)
    public function user()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
