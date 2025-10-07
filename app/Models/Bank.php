<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Bank extends Model
{
    use HasFactory;

    protected $table = 'banks';

    public $fillable = [
        'name',
        'bank_name',
        'account_number',
    ];
}
