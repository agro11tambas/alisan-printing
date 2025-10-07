<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturnEditHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'sale_return_edit_histories';

    protected $fillable = [
        'sale_return_id',
        'edited_by',
        'changes',
        'text',
        'edited_at',
    ];

    protected $casts = [
        'changes'   => 'array',      // otomatis decode JSON jadi array
        'edited_at' => 'datetime',   // supaya bisa format pakai ->format()
        'deleted_at' => 'datetime',   // supaya bisa format pakai ->format()
    ];

    // 🔹 Relasi ke SaleReturn
    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    // 🔹 Relasi ke User (siapa yang edit)
    public function user()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
