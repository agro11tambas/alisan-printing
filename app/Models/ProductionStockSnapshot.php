<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionStockSnapshot extends Model
{
    use HasFactory;

    protected $table = 'production_stock_snapshots';

    protected $fillable = [
        'product_id',
        'opening_stock',
        'closing_stock',
        'stock_in_today',
        'assign_today',
        'stock_opname_today',
        'snapshot_date',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    /**
     * Opening stock sebuah tanggal = closing stock snapshot terakhir sebelum tanggal itu.
     * Contoh: opening tanggal 7 diambil dari closing tanggal 6.
     * Kalau produk belum pernah punya snapshot sama sekali, pakai stok real saat ini
     * sebagai titik awal supaya laporan tidak mulai dari 0.
     */
    public static function resolveOpeningStock(int $productId, CarbonInterface|string $date): int
    {
        $previousClosing = static::where('product_id', $productId)
            ->whereDate('snapshot_date', '<', Carbon::parse($date)->startOfDay())
            ->orderByDesc('snapshot_date')
            ->value('closing_stock');

        if ($previousClosing !== null) {
            return (int) $previousClosing;
        }

        return (int) ProductionStock::where('product_id', $productId)->sum('available_quantity');
    }

    /**
     * Versi batch dari resolveOpeningStock: closing stock snapshot terakhir sebelum
     * tanggal tsb untuk semua produk sekaligus, dikembalikan sebagai [product_id => closing_stock].
     */
    public static function previousClosingStocks(CarbonInterface|string $date): Collection
    {
        $date = Carbon::parse($date)->startOfDay();
        $table = (new static)->getTable();

        $latest = DB::table($table)
            ->selectRaw('product_id, MAX(snapshot_date) as snapshot_date')
            ->whereDate('snapshot_date', '<', $date)
            ->groupBy('product_id');

        return DB::table($table.' as s')
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('latest.product_id', '=', 's.product_id')
                    ->on('latest.snapshot_date', '=', 's.snapshot_date');
            })
            ->pluck('s.closing_stock', 's.product_id');
    }

    /**
     * Closing stock = opening stock - assign hari ini + stock in hari ini.
     */
    public static function calculateClosingStock(int $openingStock, int $assignToday, int $stockInToday): int
    {
        return $openingStock - $assignToday + $stockInToday;
    }

    /**
     * Hitung ulang closing_stock dari kolom yang tersimpan lalu simpan.
     * Opening stock tidak pernah ikut berubah.
     */
    public function refreshClosingStock(): static
    {
        $this->closing_stock = static::calculateClosingStock(
            (int) $this->opening_stock,
            (int) $this->assign_today,
            (int) $this->stock_in_today,
        );

        $this->save();

        return $this;
    }

    public static function adjustStockOpname(int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        static::adjustColumn('stock_opname_today', $productId, $date, $adjustment);
    }

    public static function adjustAssign(int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        static::adjustColumn('assign_today', $productId, $date, $adjustment);
    }

    protected static function adjustColumn(string $column, int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        if ($adjustment === 0) {
            return;
        }

        $openingStock = static::resolveOpeningStock($productId, $date);

        $snapshot = static::firstOrCreate(
            [
                'product_id' => $productId,
                'snapshot_date' => Carbon::parse($date)->startOfDay(),
            ],
            [
                'opening_stock' => $openingStock,
                'closing_stock' => $openingStock,
            ]
        );

        $snapshot->increment($column, $adjustment);
        $snapshot->refreshClosingStock();
    }

    // ── Relations ──────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function productionStock()
    {
        return $this->belongsTo(ProductionStock::class, 'product_id', 'product_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('snapshot_date', today());
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }
}
