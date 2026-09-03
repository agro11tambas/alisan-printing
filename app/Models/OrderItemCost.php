<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ringkasan modal & margin satu baris penjualan. Denormalisasi dari
 * cost_consumptions supaya export cukup satu join, tanpa menghitung FIFO
 * ulang saat file dibuat.
 */
class OrderItemCost extends Model
{
    use HasFactory;

    protected $table = 'order_item_costs';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'product_bundle_id',
        'qty_base',
        'total_cost',
        'unit_cost',
        'revenue',
        'margin',
        'is_estimated',
    ];

    protected $casts = [
        'qty_base' => 'float',
        'total_cost' => 'float',
        'unit_cost' => 'float',
        'revenue' => 'float',
        'margin' => 'float',
        'is_estimated' => 'boolean',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
