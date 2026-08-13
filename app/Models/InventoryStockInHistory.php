<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStockInHistory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'inventory_stock_in_histories_2';

    protected $fillable = [
        'inventory_stock_in_id',
        'inventory_item_id',
        'stock_in',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function stockIn()
    {
        return $this->belongsTo(InventoryStockIn::class, 'inventory_stock_in_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function product()
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchase_product_id');
    }

    /**
     * Stock In Production harus langsung tercatat di production_stock_snapshots,
     * sama seperti assign dan stock opname. Tanpa ini stock_in_today cuma terisi
     * waktu command stock:snapshot jalan (00:00 dan 23:59), jadi stock in siang hari
     * tidak kelihatan di snapshot sampai hari itu lewat.
     */
    protected static function booted()
    {
        static::created(function (InventoryStockInHistory $history) {
            $history->syncProductionSnapshot((int) $history->stock_in);
        });

        static::updated(function (InventoryStockInHistory $history) {
            // restore() ikut lewat sini; sudah ditangani oleh event restored
            if ($history->wasChanged('deleted_at')) {
                return;
            }

            if (! $history->wasChanged('stock_in')) {
                return;
            }

            $history->syncProductionSnapshot(
                (int) $history->stock_in - (int) $history->getOriginal('stock_in')
            );
        });

        static::deleting(function (InventoryStockInHistory $history) {
            // force delete atas baris yang sudah soft-deleted: sudah dikurangi saat soft delete
            if (! $history->trashed()) {
                $history->syncProductionSnapshot(-(int) $history->stock_in);
            }
        });

        static::restored(function (InventoryStockInHistory $history) {
            $history->syncProductionSnapshot((int) $history->stock_in);
        });
    }

    /**
     * Syaratnya harus persis sama dengan ProductionStockSnapshot::stockInTodayFor():
     * cuma inventory berstatus "Stock In Production" yang asalnya dari pembelian
     * (purchase_item_id terisi). Barang dari material request tidak dihitung.
     */
    public function syncProductionSnapshot(int $adjustment): void
    {
        if ($adjustment === 0) {
            return;
        }

        $inventoryItem = $this->inventoryItem()->withTrashed()->first();
        $productId = (int) ($inventoryItem->product_id ?? 0);

        if ($productId <= 0 || $inventoryItem->purchase_item_id === null) {
            return;
        }

        $inventory = $inventoryItem->inventory()->withTrashed()->first();

        if (($inventory->status ?? null) !== 'Stock In Production') {
            return;
        }

        ProductionStockSnapshot::adjustStockIn($productId, $this->snapshotDate(), $adjustment);
    }

    /**
     * Tanggal snapshot yang dipakai untuk stock_in_today — harus sama dengan
     * dasar perhitungan command stock:snapshot, yaitu DATE(created_at) dari
     * inventory_stock_ins_2 (headernya), bukan tanggal baris history ini.
     */
    public function snapshotDate(): string
    {
        $stockIn = $this->stockIn()->withTrashed()->first();
        $createdAt = $stockIn->created_at ?? $this->created_at;

        return ($createdAt ? Carbon::parse($createdAt) : now())->toDateString();
    }
}
