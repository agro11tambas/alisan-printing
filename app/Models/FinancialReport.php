<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_reports';

    protected $fillable = [
        'date',
        'transaction_type',
        'reference_id',
        'reference_table',
        'revenue',
        'cogs',
        'cogs_fixed_cost',
        'gross_profit',
        'gross_profit_at_fixed_cost',
        'expense',
        'net_profit',
        'net_profit_at_fixed_cost',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'decimal:2',
        'cogs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'expense' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];
}
