<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialReport;
use App\Models\InventoryStock;
use App\Models\Products;
use App\Models\StockOpname;
use App\Services\FifoCostService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
{
    public function getStockOpname()
    {
        return view('erp.pages.stock-opname.stock-opname');
    }

    public function dataStockOpname(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $stockOpname = StockOpname::with('product');

        // ✅ Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $stockOpname->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $stockOpname->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $stockOpname->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $stockOpname->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $stockOpname->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $stockOpname->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $stockOpname->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        // ✅ Filter status
        if ($request->has('status') && $request->status != '') {
            $stockOpname->where('status', $request->status);
        }

        // ✅ Ambil data sesuai offset dan limit
        [$data, $hasMore] = $this->lazyLoadPage($stockOpname->latest(), $start, $length);

        // ✅ Format JSON ringan untuk lazy load
        return response()->json([
            'data' => $data->map(function ($item) {
                $date = Carbon::parse($item->created_at)->format('d M Y H:i:s');
                $status = strtolower($item->status);
                $badge = match ($status) {
                    'gain' => '<div class="badge bg-soft-success text-success">' . e($item->status) . '</div>',
                    'loss' => '<div class="badge bg-soft-danger text-danger">' . e($item->status) . '</div>',
                    default => '<div class="badge bg-soft-primary text-primary">' . e($item->status) . '</div>',
                };

                return [
                    'id' => $item->id,
                    'product_name' => e($item->product->name ?? '-'),
                    'date' => $date,
                    'quantity' => number_format($item->quantity, 0, ',', '.'),
                    'unit_cost' => $this->costCell($item),
                    'cost_value' => number_format((float) $item->cost_value, 0, ',', '.'),
                    'status' => $badge,
                    'notes' => e($item->notes ?? '-'),
                    'action' => view('erp.pages.stock-opname.partials.action-button', [
                        'stockOpname' => $item
                    ])->render(),
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Sel harga modal beserta asal angkanya.
     *
     * Asalnya ikut ditampilkan karena artinya beda: "FIFO" berarti harga batch
     * yang benar-benar termakan, "Manual" berarti seseorang mengetiknya sendiri
     * dan itu yang perlu diperiksa saat angkanya terlihat aneh.
     */
    private function costCell(StockOpname $item): string
    {
        $cost = number_format((float) $item->unit_cost, 0, ',', '.');
        $source = e($item->costSourceLabel());

        $warning = $item->is_estimated
            ? ' <i class="feather-alert-triangle text-warning" title="Sebagian kuantitasnya tidak tertutup batch mana pun, harganya taksiran"></i>'
            : '';

        return $cost . '<div class="fs-11 text-muted">' . $source . $warning . '</div>';
    }

    public function create()
    {
        $products = Products::orderBy('name', 'asc')->get();
        return view('erp.pages.stock-opname.create-stock-opname', compact('products'));
    }

    /**
     * Harga modal yang diusulkan untuk satu produk, dibaca layar saat produk
     * dipilih. Dipakai supaya operator tidak perlu menebak harga barang yang
     * ketemu lebih.
     */
    public function suggestedCost(Request $request, FifoCostService $fifo)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        [$cost, $source] = $fifo->currentCost((int) $request->product_id);

        return response()->json([
            'unit_cost' => $cost,
            'cost_source' => $source,
            'cost_source_label' => match ($source) {
                StockOpname::COST_LAST => 'Harga batch terakhir',
                default => 'Avg cost',
            },
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                              => 'required|array|min:1',
            'items.*.product_id'                 => 'required|exists:products,id',
            'items.*.inventory_warehouse_id'     => 'required|exists:inventory_warehouses,id',
            'items.*.date'                       => 'required|date',
            'items.*.quantity'                   => 'required|integer|min:0',
            'items.*.status'                     => 'required|in:Gain,Loss',
            // Hanya dipakai baris Gain, dan hanya kalau operator sengaja
            // menimpa usulan sistem. Baris Loss mengabaikannya: harganya milik
            // batch yang termakan, bukan angka yang boleh diketik.
            'items.*.unit_cost'                  => 'nullable|numeric|min:0',
            'items.*.notes'                      => 'nullable|string',
        ]);

        $productIds = [];

        DB::transaction(function () use ($request, &$productIds) {
            foreach ($request->items as $index => $item) {
                $product = Products::findOrFail($item['product_id']);
                $productIds[] = (int) $item['product_id'];

                $inventoryStock = $this->inventoryStockFor(
                    (int) $item['product_id'],
                    (int) $item['inventory_warehouse_id']
                );

                $oldStock = (int) $inventoryStock->inventory_stock;

                if ($item['status'] === 'Gain') {
                    $diff = (int) $item['quantity'];
                    $newStock = $oldStock + $diff;
                } else {
                    $diff = -(int) $item['quantity'];
                    $newStock = max(0, $oldStock + $diff);
                }

                [$unitCost, $costSource] = $this->resolveCost(
                    (int) $item['product_id'],
                    $item['status'],
                    $item['unit_cost'] ?? null,
                    "items.$index.unit_cost"
                );

                // Simpan history
                StockOpname::create([
                    'product_id'             => $item['product_id'],
                    'inventory_warehouse_id' => $item['inventory_warehouse_id'],
                    'date'        => $item['date'],
                    'quantity'    => $item['quantity'],
                    'old_stock'   => $oldStock,
                    'diff'        => $diff,
                    'unit_cost'   => $unitCost,
                    'cost_source' => $costSource,
                    // cost_value dan (untuk Loss) unit_cost diisi oleh rebuild
                    // FIFO di bawah, karena keduanya hasil hitungan antrian
                    // batch — bukan sesuatu yang bisa diketahui di sini.
                    'cost_value'  => 0,
                    'status'      => $item['status'],
                    'notes'       => $item['notes'] ?? null,
                ]);

                // Update stok per warehouse
                $inventoryStock->update([
                    'inventory_stock'   => $newStock,
                    'stock_after_sales' => $inventoryStock->stock_after_sales + $diff,
                ]);

                // Update total stok produk
                $totalInventory = InventoryStock::where('product_id', $item['product_id'])->sum('inventory_stock');
                $totalAfterSales = InventoryStock::where('product_id', $item['product_id'])->sum('stock_after_sales');

                $product->update([
                    'inventory_stock'   => $totalInventory,
                    'stock_after_sales' => $totalAfterSales,
                ]);
            }

            $this->recost($productIds);
        });

        return redirect('/erp/inventory/stock-opname')
            ->with('success', 'Stock Opname created successfully.');
    }

    public function edit($id)
    {
        $stockOpname = StockOpname::with('costLayers.costLayer')->findOrFail($id);

        $products = Products::all();

        return view('erp.pages.stock-opname.edit-stock-opname', compact('stockOpname', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product'                 => 'required|exists:products,id',
            'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
            'date'                    => 'required|date',
            'quantity'                => 'required|integer|min:0',
            'status'                  => 'required|in:Gain,Loss',
            'unit_cost'               => 'nullable|numeric|min:0',
            'notes'                   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Ambil stock opname lama
            $stockOpname = StockOpname::findOrFail($id);

            // Produk lama ikut dihitung ulang: kalau produknya dipindah, antrian
            // batch produk yang ditinggalkan juga berubah.
            $productIds = array_unique([
                (int) $stockOpname->product_id,
                (int) $request->product,
            ]);

            // Ambil stok per warehouse
            $inventoryStock = $this->inventoryStockFor(
                (int) $request->product,
                (int) $request->inventory_warehouse_id
            );

            // 1️⃣ Revert efek opname lama
            if ($stockOpname->status === 'Gain') {
                $inventoryStock->inventory_stock   -= $stockOpname->quantity;
                $inventoryStock->stock_after_sales -= $stockOpname->quantity;
            } else { // Loss
                $inventoryStock->inventory_stock   += $stockOpname->quantity;
                $inventoryStock->stock_after_sales += $stockOpname->quantity;
            }

            // Pastikan gak minus
            $inventoryStock->inventory_stock   = max(0, $inventoryStock->inventory_stock);
            $inventoryStock->stock_after_sales = max(0, $inventoryStock->stock_after_sales);

            // Simpan hasil revert
            $inventoryStock->save();

            $oldStock = (int) $inventoryStock->inventory_stock;

            // 2️⃣ Hitung efek opname baru
            if ($request->status === 'Gain') {
                $diff     = (int) $request->quantity;
                $newStock = $oldStock + $diff;
            } else { // Loss
                $diff     = -(int) $request->quantity;
                $newStock = max(0, $oldStock + $diff);
            }

            // 3️⃣ Harga modalnya ditentukan ulang.
            //
            // Kalau status berubah dari Loss jadi Gain, harga FIFO yang lama
            // tidak berlaku lagi: dia harga batch yang termakan, sedangkan Gain
            // butuh harga untuk batch yang dilahirkan. Jadi diambil baru,
            // kecuali operator mengetik angkanya sendiri.
            [$unitCost, $costSource] = $this->resolveCost(
                (int) $request->product,
                $request->status,
                $request->unit_cost,
                'unit_cost',
                $stockOpname
            );

            // 4️⃣ Update inventory_stocks
            $inventoryStock->update([
                'inventory_stock'   => $newStock,
                'stock_after_sales' => max(0, $inventoryStock->stock_after_sales + $diff),
            ]);

            // 5️⃣ Update stok global produk
            $product = Products::findOrFail($request->product);
            $totalInventory  = InventoryStock::where('product_id', $request->product)->sum('inventory_stock');
            $totalAfterSales = InventoryStock::where('product_id', $request->product)->sum('stock_after_sales');

            $product->update([
                'inventory_stock'   => $totalInventory,
                'stock_after_sales' => $totalAfterSales,
            ]);

            // 6️⃣ Update record opname
            $stockOpname->update([
                'product_id'             => $request->product,
                'inventory_warehouse_id' => $request->inventory_warehouse_id,
                'date'        => $request->date,
                'quantity'    => $request->quantity,
                'old_stock'   => $oldStock,
                'diff'        => $diff,
                'unit_cost'   => $unitCost,
                'cost_source' => $costSource,
                'cost_value'  => 0,
                'status'      => $request->status,
                'notes'       => $request->notes,
            ]);

            $this->recost($productIds);

            DB::commit();

            return redirect('/erp/inventory/stock-opname')
                ->with('success', 'Stock Opname updated successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update Stock Opname: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            // Ambil stock opname yang mau dihapus
            $stockOpname = StockOpname::findOrFail($id);
            $productId = (int) $stockOpname->product_id;

            // Ambil inventory stock (per warehouse)
            $inventoryStock = InventoryStock::where('product_id', $stockOpname->product_id)
                ->where('inventory_warehouse_id', $stockOpname->inventory_warehouse_id)
                ->first();

            if ($inventoryStock) {
                $currentStock = (int) $inventoryStock->inventory_stock;

                // Revert efek opname yang mau dihapus
                if ($stockOpname->status === 'Gain') {
                    $newStock = max(0, $currentStock - $stockOpname->quantity);
                } else { // Loss
                    $newStock = $currentStock + $stockOpname->quantity;
                }

                // Update stok di warehouse
                $inventoryStock->update([
                    'inventory_stock'   => $newStock,
                    'stock_after_sales' => $newStock,
                ]);

                // Update stok global produk
                $totalStock = InventoryStock::where('product_id', $stockOpname->product_id)->sum('inventory_stock');
                $product = Products::findOrFail($stockOpname->product_id);
                $product->update([
                    'inventory_stock'   => $totalStock,
                    'stock_after_sales' => $totalStock,
                ]);
            }

            // Rincian batch dan baris bebannya ikut hilang. Tanpa ini, laporan
            // laba rugi masih membebani selisih dari opname yang sudah tidak ada.
            $stockOpname->costLayers()->delete();

            FinancialReport::where('transaction_type', FifoCostService::FINANCIAL_TYPE_OPNAME)
                ->where('reference_table', 'stock_opnames')
                ->where('reference_id', $stockOpname->id)
                ->forceDelete();

            // Hapus record stock opname
            $stockOpname->delete();

            $this->recost([$productId]);
        });

        return redirect('/erp/inventory/stock-opname')
            ->with('success', 'Stock Opname deleted successfully.');
    }

    /**
     * Tentukan harga modal satu baris opname.
     *
     * Loss : dikosongkan. Harganya hasil hitungan antrian FIFO, dan yang
     *        mengisinya FifoCostService saat rebuild — bukan layar ini.
     * Gain : butuh harga, karena batch tanpa harga akan menilai penjualan
     *        berikutnya dengan modal nol. Diambil dari avg cost (rata-rata
     *        tertimbang sisa batch), lalu harga batch terakhir kalau stoknya
     *        habis. Angka manual menang kalau diisi.
     *
     * @return array{0: float, 1: string}
     */
    private function resolveCost(
        int $productId,
        string $status,
        $manual,
        string $field,
        ?StockOpname $existing = null
    ): array {
        if (strcasecmp($status, 'Gain') !== 0) {
            return [0.0, StockOpname::COST_FIFO];
        }

        $manual = ($manual === null || $manual === '') ? null : (float) $manual;

        // Baris Gain yang harganya memang diketik manual tidak boleh diam-diam
        // kembali ke avg cost cuma karena form-nya disimpan ulang tanpa mengubah
        // kolom itu.
        if ($manual === null
            && $existing !== null
            && $existing->isGain()
            && $existing->cost_source === StockOpname::COST_MANUAL
            && (float) $existing->unit_cost > 0
        ) {
            return [(float) $existing->unit_cost, StockOpname::COST_MANUAL];
        }

        if ($manual !== null && $manual > 0) {
            return [round($manual, 5), StockOpname::COST_MANUAL];
        }

        [$cost, $source] = app(FifoCostService::class)->currentCost($productId);

        // Nol berarti produknya belum punya harga modal sama sekali: belum ada
        // pembelian, dan opening rate-nya juga belum diisi. Disimpan begitu saja
        // dia jadi batch bermodal nol yang menelan penjualan-penjualan
        // berikutnya, jadi lebih baik ditolak di sini — kesalahannya masih
        // kelihatan sekarang, bukan nanti di laporan margin.
        if ($cost <= 0) {
            throw ValidationException::withMessages([
                $field => 'Produk ini belum punya harga modal (belum ada pembelian dan Opening Rate masih kosong). '
                    . 'Isi Unit Cost secara manual, atau lengkapi Opening Stock & Rate produknya dulu.',
            ]);
        }

        return [$cost, $source];
    }

    private function inventoryStockFor(int $productId, int $warehouseId): InventoryStock
    {
        return InventoryStock::firstOrCreate(
            [
                'product_id'             => $productId,
                'inventory_warehouse_id' => $warehouseId,
            ],
            [
                'opening_stock'     => 0,
                'opening_rate'      => 0,
                'inventory_stock'   => 0,
                'incoming_stock'    => 0,
                'stock_after_sales' => 0,
                'avg_cost'          => 0,
            ]
        );
    }

    /**
     * Hitung ulang ledger FIFO produk-produk yang tersentuh.
     *
     * Wajib dipanggil setiap baris opname lahir, berubah, atau hilang: selisih
     * hitung fisik memakan (atau menambah) antrian batch, jadi nilai persediaan
     * dan avg cost seluruh produk itu ikut bergerak. Tanpa langkah ini stoknya
     * berubah tapi nilainya tidak, dan keduanya jadi tidak cocok.
     *
     * Rebuild-nya bertarget produk, bukan penuh — rebuild penuh tempatnya di
     * cron malam, bukan di dalam request.
     *
     * @param  array<int>  $productIds
     */
    private function recost(array $productIds): void
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return;
        }

        app(FifoCostService::class)->rebuild($productIds);
    }
}
