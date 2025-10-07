<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermAndCondition extends Model
{
    use HasFactory;
    
    protected $table = 'term_and_conditions';

    protected $fillable = [
        'invoice_id',
        'content'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
