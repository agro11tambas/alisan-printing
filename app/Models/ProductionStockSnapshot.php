<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionStockSnapshot extends Model
{
    use HasFactory;

    protected $table = 'production_stock_snapshots';

    protected $fillable = [
        'product_id',
        'opening_stock',
        'closing_stock',
        'stock_in_today',
        'assign_today',
        'snapshot_date',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    // ── Relations ──────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function productionStock()
    {
        return $this->belongsTo(ProductionStock::class, 'product_id', 'product_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('snapshot_date', today());
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }
}
