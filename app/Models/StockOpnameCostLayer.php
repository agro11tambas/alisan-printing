<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian batch FIFO yang dipakai satu baris Stock Opname.
 *
 * Untuk Loss: batch-batch yang termakan beserta harganya masing-masing —
 * satu selisih kurang bisa memakan beberapa batch sekaligus.
 * Untuk Gain: satu baris yang menunjuk batch baru hasil opname itu sendiri.
 *
 * Dibangun ulang tiap rebuild FIFO, jadi jangan diedit dari luar.
 */
class StockOpnameCostLayer extends Model
{
    protected $table = 'stock_opname_cost_layers';

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'cost_layer_id',
        'qty',
        'unit_cost',
        'subtotal',
        'is_estimated',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'float',
        'subtotal' => 'float',
        'is_estimated' => 'boolean',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function costLayer(): BelongsTo
    {
        return $this->belongsTo(CostLayer::class, 'cost_layer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }
}
