<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameProduction extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_productions';

    protected $fillable = [
        'product_id',
        'production_warehouse_id',
        'date',
        'change',
        'finished_product',
        'available_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (StockOpnameProduction $stockOpname) {
            ProductionStockSnapshot::adjustStockOpname(
                $stockOpname->product_id,
                $stockOpname->date,
                $stockOpname->signedQuantity()
            );
        });

        static::updated(function (StockOpnameProduction $stockOpname) {
            $originalQuantity = static::signedQuantityFrom(
                $stockOpname->getOriginal('status'),
                $stockOpname->getOriginal('available_quantity')
            );

            ProductionStockSnapshot::adjustStockOpname(
                $stockOpname->getOriginal('product_id'),
                $stockOpname->getOriginal('date'),
                -$originalQuantity
            );

            ProductionStockSnapshot::adjustStockOpname(
                $stockOpname->product_id,
                $stockOpname->date,
                $stockOpname->signedQuantity()
            );
        });

        static::deleted(function (StockOpnameProduction $stockOpname) {
            ProductionStockSnapshot::adjustStockOpname(
                $stockOpname->product_id,
                $stockOpname->date,
                -$stockOpname->signedQuantity()
            );
        });
    }

    public function signedQuantity(): int
    {
        return static::signedQuantityFrom($this->status, $this->available_quantity);
    }

    private static function signedQuantityFrom(?string $status, mixed $quantity): int
    {
        $quantity = (int) $quantity;

        return strcasecmp((string) $status, 'Loss') === 0 ? -$quantity : $quantity;
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
