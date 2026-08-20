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
     * Nilai stock_in_today, assign_today, dan stock_opname_today dihitung ulang dari
     * tabel sumbernya, bukan dari kolom yang tersimpan. Dipakai bareng-bareng oleh
     * command stock:snapshot, command perbaikan, dan report supaya rumusnya cuma ada di satu tempat.
     */
    public static function stockInTodayFor(int $productId, CarbonInterface|string $date): int
    {
        $date = Carbon::parse($date)->toDateString();

        // Cuma stock in yang asalnya dari pembelian (PO/Purchase List) yang dihitung.
        // Barang dari material request tidak dihitung sebagai stock in — itu cuma
        // pindah gudang, bukan barang baru masuk.
        $fromInventory = DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as s', 's.id', '=', 'h.inventory_stock_in_id')
            ->join('inventory_items_2 as i', 'i.id', '=', 'h.inventory_item_id')
            ->join('inventories_2 as inv', 'inv.id', '=', 'i.inventory_id')
            ->where('i.product_id', $productId)
            ->where('inv.status', 'Stock In Production')
            ->whereNotNull('i.purchase_item_id')
            ->whereNull('h.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereDate('s.created_at', $date)
            ->sum('h.stock_in');

        return (int) ($fromInventory ?? 0);
    }

    public static function stockInTodayByProduct(iterable $productIds, CarbonInterface|string $date): Collection
    {
        $productIds = collect($productIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as s', 's.id', '=', 'h.inventory_stock_in_id')
            ->join('inventory_items_2 as i', 'i.id', '=', 'h.inventory_item_id')
            ->join('inventories_2 as inv', 'inv.id', '=', 'i.inventory_id')
            ->whereIn('i.product_id', $productIds)
            ->where('inv.status', 'Stock In Production')
            ->whereNotNull('i.purchase_item_id')
            ->whereNull('h.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereDate('s.created_at', Carbon::parse($date)->toDateString())
            ->groupBy('i.product_id')
            ->selectRaw('i.product_id, SUM(h.stock_in) as total')
            ->pluck('total', 'i.product_id');
    }
    public static function assignTodayFor(int $productId, CarbonInterface|string $date): int
    {
        return (int) OrderProgressAssign::where('product_id', $productId)
            ->whereNull('deleted_at')
            ->whereDate('created_at', Carbon::parse($date)->toDateString())
            ->sum('assigned_quantity');
    }

    public static function assignTodayByProduct(iterable $productIds, CarbonInterface|string $date): Collection
    {
        $productIds = collect($productIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return OrderProgressAssign::query()
            ->whereIn('product_id', $productIds)
            ->whereDate('created_at', Carbon::parse($date)->toDateString())
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(assigned_quantity) as total')
            ->pluck('total', 'product_id');
    }
    public static function stockOpnameTodayFor(int $productId, CarbonInterface|string $date): int
    {
        return (int) StockOpnameProduction::where('product_id', $productId)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->get()
            ->sum(fn (StockOpnameProduction $stockOpname) => $stockOpname->signedQuantity());
    }

    public static function stockOpnameTodayByProduct(iterable $productIds, CarbonInterface|string $date): Collection
    {
        $productIds = collect($productIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return StockOpnameProduction::query()
            ->whereIn('product_id', $productIds)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->groupBy('product_id')
            ->selectRaw("product_id, SUM(CASE WHEN LOWER(status) = 'loss' THEN -COALESCE(available_quantity, 0) ELSE COALESCE(available_quantity, 0) END) as total")
            ->pluck('total', 'product_id');
    }
    /**
     * Closing stock = opening stock - assign hari ini + stock in hari ini + stock opname hari ini.
     * stock_opname_today sudah bertanda (Loss bernilai negatif), jadi cukup ditambahkan.
     */
    public static function calculateClosingStock(int $openingStock, int $assignToday, int $stockInToday, int $stockOpnameToday): int
    {
        return $openingStock - $assignToday + $stockInToday + $stockOpnameToday;
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
            (int) $this->stock_opname_today,
        );

        $this->save();

        return $this;
    }

    /**
     * Closing sebuah tanggal berubah => opening semua snapshot SESUDAHNYA ikut berubah,
     * karena opening tanggal N selalu turunan dari closing tanggal N-1.
     * Tanpa ini, koreksi stock opname (apalagi yang backdate) berhenti di tanggalnya sendiri
     * dan hilang begitu snapshot hari berikutnya sudah terlanjur dibuat.
     */
    public static function rebuildChainFrom(int $productId, CarbonInterface|string $date): void
    {
        $date = Carbon::parse($date)->startOfDay();

        $previousClosing = static::where('product_id', $productId)
            ->whereDate('snapshot_date', '<', $date)
            ->orderByDesc('snapshot_date')
            ->value('closing_stock');

        $snapshots = static::where('product_id', $productId)
            ->whereDate('snapshot_date', '>=', $date)
            ->orderBy('snapshot_date')
            ->get();

        foreach ($snapshots as $snapshot) {
            // Snapshot paling awal tidak punya acuan sebelumnya, opening-nya dibiarkan
            // apa adanya (titik awal dari stok real waktu snapshot itu dibuat).
            if ($previousClosing !== null) {
                $snapshot->opening_stock = (int) $previousClosing;
            }

            $snapshot->refreshClosingStock();

            $previousClosing = (int) $snapshot->closing_stock;
        }
    }

    public static function adjustStockOpname(int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        static::adjustColumn('stock_opname_today', $productId, $date, $adjustment);
    }

    public static function adjustAssign(int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        static::adjustColumn('assign_today', $productId, $date, $adjustment);
    }

    public static function adjustStockIn(int $productId, CarbonInterface|string $date, int $adjustment): void
    {
        static::adjustColumn('stock_in_today', $productId, $date, $adjustment);
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

        // Bukan cuma baris tanggal ini: rantai ke depan harus ikut dihitung ulang.
        static::rebuildChainFrom($productId, $date);
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
