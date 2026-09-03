<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_bundle_id',
        'status',
        'product_name',
        'price_mode_id',
        'satuan',
        'mode',
        'quantity',
        'completed_quantity',
        'price',
        'subtotal',
        'discount_price',
        'total_after_discount',
        'stock_out',
        'product_unit_conversion_id',
        'unit_name',
        'unit_conversion_value',
        'qty_base',
        'product_bundle_unit_conversion_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',

        'quantity' => 'integer',
        'completed_quantity' => 'integer',

        'price' => 'integer',
        'subtotal' => 'integer',
        'discount_price' => 'integer',
        'total_after_discount' => 'integer',

        'qty_base' => 'integer',
        'unit_conversion_value' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // public function deliveryItems()
    // {
    //     return $this->hasMany(DeliveryItemHistory::class);
    // }

    public function productUnitConversion(): BelongsTo
    {
        return $this->belongsTo(ProductUnitConversion::class, 'product_unit_conversion_id');
    }

    public function productBundleUnitConversion()
    {
        return $this->belongsTo(ProductBundleUnitConversion::class, 'product_bundle_unit_conversion_id');
    }

    public function deliveryListItems()
    {
        return $this->hasMany(DeliveryListItem::class, 'delivery_order_item_id');
    }

    public function priceMode(): BelongsTo
    {
        return $this->belongsTo(PriceMode::class);
    }

    public function usesProductionFlow(): bool
    {
        if ($this->relationLoaded('priceMode') || $this->price_mode_id) {
            return ($this->priceMode?->production_flow ?? 'production') === 'production';
        }

        return $this->mode !== 'polosan';
    }

    public function usesPolosanFlow(): bool
    {
        return !$this->usesProductionFlow();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function productBundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id')->withTrashed();
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'order_item_id');
    }

    public function components()
    {
        return $this->hasMany(OrderItemComponent::class, 'order_item_id');
    }

    /**
     * Harga modal & margin FIFO baris ini. Diisi oleh command cost:rebuild-fifo,
     * bukan saat order disimpan.
     */
    public function cost(): HasOne
    {
        return $this->hasOne(OrderItemCost::class, 'order_item_id');
    }

    /** Rincian batch pembelian yang dimakan baris ini. */
    public function costConsumptions(): HasMany
    {
        return $this->hasMany(CostConsumption::class, 'order_item_id');
    }

    public function designItems()
    {
        return $this->hasMany(DesignItem::class, 'order_item_id');
    }

    // public function getCompletedQuantityAttribute(): int
    // {
    //     return $this->orderProgress()->sum('completed_quantity');
    // }

    // public function getProgressPercentAttribute(): float
    // {
    //     return $this->quantity > 0
    //         ? round(($this->completed_quantity / $this->quantity) * 100, 2)
    //         : 0;
    // }

    protected static function booted()
    {
        static::saving(function ($orderItem) {
            if ($orderItem->mode && Schema::hasTable('price_modes')) {
                $orderItem->price_mode_id = PriceMode::where('slug', $orderItem->mode)->value('id');
            }
        });

        static::deleting(function ($orderItem) {
            if ($orderItem->isForceDeleting()) {
                // ✅ Force delete semua relasi anak
                $orderItem->components()->get()->each->forceDelete();
                $orderItem->designItems()->get()->each->forceDelete();
                // $orderItem->deliveryItems()->get()->each->forceDelete();
                $orderItem->inventoryItems()->get()->each->forceDelete();
                // $orderItem->orderProgress()->get()->each->forceDelete();                

                // kalau nanti ada relasi tambahan lain, tinggal tambahkan di sini
            } else {
                // 💤 Soft delete semua relasi anak
                $orderItem->components()->get()->each->delete();
                $orderItem->designItems()->get()->each->delete();
                // $orderItem->deliveryItems()->get()->each->delete();
                $orderItem->inventoryItems()->get()->each->delete();
                // $orderItem->orderProgress()->get()->each->delete();
            }
        });

        static::restoring(function ($orderItem) {
            // ♻️ Restore semua anak yang ikut soft delete
            $orderItem->components()->get()->each->restore();
            $orderItem->designItems()->get()->each->restore();
            // $orderItem->deliveryItems()->get()->each->restore();
            $orderItem->inventoryItems()->get()->each->restore();
            // $orderItem->orderProgress()->get()->each->restore();
        });
    }
}
