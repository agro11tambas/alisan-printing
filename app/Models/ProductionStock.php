<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionStock extends Model
{
    use HasFactory;

    protected $table = 'production_stocks';

    protected $fillable = [
        'production_warehouse_id',
        'product_id',
        'opening_stock',
        'opening_finished_product_stock',
        'finished_product_stock',
        'canceled_product_stock',
        'available_quantity',
        'pending_waiting_list',
        'incoming_stock',
    ];

    public function warehouse()
    {
        return $this->belongsTo(ProductionWarehouse::class, 'production_warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function orderProgressItems()
    {
        return $this->hasMany(OrderProgressItem::class, 'product_id', 'product_id');
    }

    public function canceledProducts()
    {
        return $this->hasMany(CanceledProduct::class, 'production_stock_id');
    }

    public function designItems()
    {
        return $this->hasMany(DesignItem::class, 'product_id', 'product_id');
    }

    public function todaySnapshot()
    {
        return $this->hasOne(ProductionStockSnapshot::class, 'product_id', 'product_id')
            ->whereDate('snapshot_date', today()->subDay());
    }

    // public function getRemainingQuantityAttribute()
    // {
    //     $totalDesignQty = $this->designItems()
    //         ->select(DB::raw('COALESCE(SUM(quantity), 0) as total'))
    //         ->value('total') ?? 0;

    //     $totalCompletedQty = $this->orderProgressItems()
    //         ->select(DB::raw('COALESCE(SUM(completed_quantity), 0) as total'))
    //         ->value('total') ?? 0;

    //     return max($totalDesignQty - $totalCompletedQty, 0);
    // }

    /**
     * 🔒 Pastikan stok cukup sebelum available_quantity dikurangi sebanyak $qty.
     *
     * Dilewati kalau setting `allow_negative_stock` aktif. Stok dibaca ulang
     * dengan lock baris supaya dua request paralel tidak sama-sama lolos cek
     * lalu sama-sama mengurangi stok sampai minus.
     *
     * Wajib dipanggil di dalam transaction (lockForUpdate butuh transaction).
     */
    public function assertCanReduce(int $qty, string $productName, string $field = 'assigned_quantity'): void
    {
        if ($qty <= 0 || Setting::isEnabled('allow_negative_stock')) {
            return;
        }

        $available = (int) static::whereKey($this->getKey())
            ->lockForUpdate()
            ->value('available_quantity');

        if ($available <= 0) {
            throw ValidationException::withMessages([
                $field => "Stok available 0 untuk produk {$productName}.",
            ]);
        }

        if ($qty > $available) {
            throw ValidationException::withMessages([
                $field => "Assigned quantity ($qty) melebihi stok available ($available) untuk produk {$productName}.",
            ]);
        }
    }
}
