<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak "baris penjualan ini memakan batch mana, berapa, di harga berapa".
 * Inilah yang menjawab pertanyaan "kenapa harga modalnya 375?".
 */
class CostConsumption extends Model
{
    use HasFactory;

    protected $table = 'cost_consumptions';

    protected $fillable = [
        'order_id',
        'order_item_id',
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

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
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
