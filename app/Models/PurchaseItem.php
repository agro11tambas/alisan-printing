<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'source_purchase_item_id',
        'product_id',
        'product_unit_conversion_id',
        'unit_name',
        'unit_conversion_value',
        'qty_base',
        'inventory_warehouse_id',
        'production_warehouse_id',
        'status',
        'quantity',
        'price',
        'price_after_tax',
        'freight',
        'final_price',
        'subtotal',
        'stock_in',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'quantity' => 'integer',
        'qty_base' => 'integer',
        'unit_conversion_value' => 'float',
    ];

    public function purchaseProduct(): BelongsTo
    {
        // withTrashed: produk yang sudah dihapus tetap tampil di dokumen lama.
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function sourcePurchaseItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_purchase_item_id');
    }

    public function purchaseListItems()
    {
        return $this->hasMany(self::class, 'source_purchase_item_id');
    }

    public function scopeAccount($query)
    {
        return $query->where('status', 'Purchase Account');
    }

    public function scopeReturn($query)
    {
        return $query->where('status', 'Purchase Return');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'purchase_item_id');
    }

    /**
     * Inventory item milik seluruh item Purchase List turunan item PO ini.
     *
     * Dipakai listing Purchase Order untuk menjumlahkan Stock In lewat satu
     * withSum, bukan dengan memuat pohon purchaseListItems.inventoryItems dan
     * menjumlahkannya di PHP.
     */
    public function purchaseListInventoryItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryItem::class,
            self::class,
            'source_purchase_item_id', // FK di purchase_items (item PL) -> item PO
            'purchase_item_id',        // FK di inventory_items_2 -> item PL
            'id',
            'id'
        );
    }

    /**
     * Realisasi Stock In item PL ini (dalam satuan dasar/pcs).
     * Diambil dari inventory item supaya ikut perubahan lewat edit history.
     */
    public function stockInBase(): float
    {
        return (float) $this->inventoryItems->sum('stock_in');
    }

    public function isFullyStockedIn(): bool
    {
        $target = (float) ($this->qty_base ?: $this->quantity);

        return $target > 0 && $this->stockInBase() >= $target;
    }

    public function inventoryReturnItems()
    {
        return $this->hasMany(InventoryItem::class, 'purchase_return_item_id');
    }

    public function productUnitConversion(): BelongsTo
    {
        return $this->belongsTo(ProductUnitConversion::class, 'product_unit_conversion_id');
    }
}
