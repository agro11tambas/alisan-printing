<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Selisih hasil hitung fisik gudang.
 *
 * Nilainya ikut metode FIFO, bukan angka yang diketik bebas:
 *
 * - Loss  : mengeluarkan barang dari antrian batch tertua, persis seperti
 *           penjualan. unit_cost-nya HASIL hitungan, tidak pernah diinput.
 * - Gain  : melahirkan batch baru. unit_cost-nya dibekukan saat disimpan,
 *           default dari harga rata-rata sisa stok (avg cost).
 *
 * Yang menghitungnya FifoCostService, dan dia menghitung ulang dari nol tiap
 * rebuild — jadi baris ini adalah masukan untuk FIFO, bukan turunannya.
 */
class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'product_id',
        'inventory_warehouse_id',
        'date',
        'quantity',
        'old_stock',
        'diff',
        'unit_cost',
        'cost_value',
        'cost_source',
        'is_estimated',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'float',
        'old_stock' => 'float',
        'diff' => 'float',
        'unit_cost' => 'float',
        'cost_value' => 'float',
        'is_estimated' => 'boolean',
    ];

    /** Sumber harga modal untuk baris Gain, urut dari yang paling disukai. */
    public const COST_AVG = 'avg_cost';

    public const COST_LAST = 'last_cost';

    public const COST_MANUAL = 'manual';

    /** Baris Loss: harganya milik batch yang termakan, jadi selalu ini. */
    public const COST_FIFO = 'fifo';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function costLayers(): HasMany
    {
        return $this->hasMany(StockOpnameCostLayer::class, 'stock_opname_id');
    }

    public function isGain(): bool
    {
        return strcasecmp((string) $this->status, 'Gain') === 0;
    }

    /** Kuantitas bertanda: positif untuk Gain, negatif untuk Loss. */
    public function signedQuantity(): float
    {
        return $this->isGain() ? (float) $this->quantity : -(float) $this->quantity;
    }

    /** Label sumber harga untuk ditampilkan di layar. */
    public function costSourceLabel(): string
    {
        return match ($this->cost_source) {
            self::COST_FIFO => 'FIFO (batch termakan)',
            self::COST_AVG => 'Avg cost',
            self::COST_LAST => 'Harga batch terakhir',
            self::COST_MANUAL => 'Manual',
            default => '-',
        };
    }
}
