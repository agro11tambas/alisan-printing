<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu batch stok masuk beserta harga modalnya. Antrian FIFO dibaca urut
 * layer_date lalu id.
 */
class CostLayer extends Model
{
    use HasFactory;

    protected $table = 'cost_layers';

    public const SOURCE_PURCHASE = 'purchase_item';

    public const SOURCE_OPENING = 'opening_stock';

    /**
     * Stok yang ketemu lebih banyak saat hitung fisik. Duduk di ekor antrian
     * (layer_date = akhir hari opname) karena umur barangnya tidak diketahui:
     * memperlakukannya sebagai batch terbaru adalah pilihan yang paling aman.
     */
    public const SOURCE_OPNAME = 'stock_opname';

    /**
     * Batch gudang yang diaktifkan oleh Request Stock. source_id menunjuk ke
     * batch gudang asalnya, harganya sama, tanggalnya tanggal pengiriman.
     */
    public const SOURCE_TRANSFER = 'request_stock';

    /** Barang masih di gudang: batch pasif, tidak dimakan penjualan. */
    public const LOCATION_WAREHOUSE = 'warehouse';

    /** Barang sudah di produksi: batch aktif, dimakan penjualan. */
    public const LOCATION_PRODUCTION = 'production';

    protected $fillable = [
        'product_id',
        'source_type',
        'source_id',
        'location',
        'reference',
        'layer_date',
        'qty_in',
        'qty_remaining',
        'unit_cost',
    ];

    protected $casts = [
        'layer_date' => 'datetime',
        'qty_in' => 'float',
        'qty_remaining' => 'float',
        'unit_cost' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class, 'source_id');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(CostConsumption::class, 'cost_layer_id');
    }

    public function opnameCostLayers(): HasMany
    {
        return $this->hasMany(StockOpnameCostLayer::class, 'cost_layer_id');
    }
}
