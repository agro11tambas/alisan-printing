<?php

namespace App\Services;

use App\Models\CostLayer;
use App\Models\CostSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghitung harga modal (HPP) tiap baris penjualan dengan metode FIFO.
 *
 * Sebuah batch baru lahir ketika barangnya BENAR-BENAR MASUK GUDANG (stock in),
 * bukan ketika Purchase Order atau Purchase List dibuat. Purchase yang sudah
 * diketik tapi barangnya belum datang tidak ikut menilai penjualan mana pun.
 *
 * Cara kerjanya: seluruh stok masuk disusun jadi antrian batch (cost_layers),
 * lalu seluruh penjualan dan retur diputar ulang urut tanggal dan memakan
 * antrian itu dari yang paling tua. Satu baris penjualan boleh memakan lebih
 * dari satu batch; harga modal barisnya adalah rata-rata tertimbang batch yang
 * termakan.
 *
 * Contoh: batch 1.000@300, 1.000@350, 1.000@400. Setelah terjual 1.500,
 * penjualan berikutnya sebanyak 1.000 memakan 500@350 + 500@400, sehingga
 * total modalnya 375.000 dan harga modal per pcs-nya 375.
 *
 * Konversi satuan ditangani di sini: beli 1 Dus @1.000.000 dengan 1 Dus = 1.000
 * Pcs menghasilkan batch 1.000 Pcs @1.000. Semua kuantitas di kelas ini memakai
 * SATUAN DASAR (qty_base), supaya sisi pembelian dan penjualan sebanding.
 */
class FifoCostService
{
    /** Ukuran batch insert. Menahan memori tetap datar pada data besar. */
    private const INSERT_CHUNK = 500;

    /**
     * Tanggal semu untuk layer opening stock supaya selalu paling depan di
     * antrian FIFO, berapa pun tanggal purchase yang ada.
     */
    private const OPENING_DATE = '1970-01-01 00:00:00';

    /**
     * Status purchase_items yang dianggap pembelian nyata. Item Purchase Order
     * (status null, harganya masih 0) tidak ikut: itu baru pesanan.
     *
     * Ini hanya saringan harga; yang menentukan sebuah batch ada atau tidak
     * adalah kejadian stock in-nya, lihat buildLayers().
     */
    private const PURCHASE_ITEM_STATUS = 'Purchase Account';

    /** Status order yang dianggap penjualan terealisasi. */
    private const SALE_ORDER_STATUS = 'Sale List';

    /** Batas putaran perluasan scope sebelum menyerah dan rebuild penuh. */
    private const SCOPE_EXPANSION_ROUNDS = 5;

    private const EPSILON = 0.00001;

    /**
     * Antrian batch per produk selama replay berlangsung.
     *
     * @var array<int, array<int, array{id:int, qty:float, cost:float, date:string}>>
     */
    private array $queues = [];

    /** Penunjuk batch terdepan yang belum habis, per produk. */
    private array $cursors = [];

    /**
     * Batas batch yang sudah "datang" pada titik waktu replay saat ini, per
     * produk. Penjualan tanggal 1 tidak boleh memakan batch tanggal 5.
     */
    private array $available = [];

    /** Harga batch terakhir yang sudah datang, per produk. */
    private array $lastAvailableCost = [];

    /** Harga batch paling awal per produk, untuk penjualan sebelum ada pembelian. */
    private array $firstLayerCost = [];

    /** Cadangan terakhir untuk produk yang tidak punya batch sama sekali. */
    private array $fallbackProductCost = [];

    /** Sisa kuantitas tiap layer setelah replay, untuk disimpan balik. */
    private array $layerRemaining = [];

    /** Komponen bundle: [bundle_id => [[product_id, qty_per_unit], ...]]. */
    private array $bundleComponents = [];

    /**
     * Batch yang dimakan tiap baris penjualan, dipakai saat retur membalikkan
     * stok ke batch yang sama.
     *
     * @var array<int, array<int, array{product:int, layer:int|null, index:int|null, qty:float, cost:float}>>
     */
    private array $saleAllocations = [];

    /** Kuantitas dasar tiap baris penjualan, untuk menghitung porsi retur. */
    private array $saleQtyBase = [];

    /** Akumulasi retur per baris penjualan: [order_item_id => [qty, cost]]. */
    private array $returnAdjustments = [];

    private array $consumptionBuffer = [];

    private array $orderCostBuffer = [];

    /**
     * Tanggal mulai pembukuan FIFO, atau null kalau seluruh riwayat dipakai.
     * Dibaca sekali per rebuild.
     */
    private ?Carbon $startDate = null;

    /** Produk yang punya stok awal tapi opening rate-nya belum diisi. */
    private array $openingWithoutRate = [];

    private array $stats = [
        'layers' => 0,
        'order_items' => 0,
        'consumptions' => 0,
        'estimated_items' => 0,
        'returns' => 0,
    ];

    /**
     * Bangun ulang cost layer dan alokasi FIFO dari nol.
     *
     * Rebuild dipilih ketimbang update inkremental karena satu purchase yang
     * diedit atau dibackdate mengubah alokasi seluruh penjualan sesudahnya.
     * Menghitung ulang jauh lebih mudah dipercaya daripada menambal alokasi
     * lama.
     *
     * @param  array<int>|null  $productIds  batasi ke produk tertentu; null = semua
     * @return array<string, int> ringkasan jumlah baris yang dihasilkan
     */
    public function rebuild(?array $productIds = null): array
    {
        $this->startDate = CostSetting::startDate();

        $scope = $productIds === null ? null : $this->expandScope($productIds);

        DB::transaction(function () use ($scope) {
            $this->clear($scope);
            $this->buildLayers($scope);
            $this->loadQueues($scope);
            $this->replayTimeline($scope);
            $this->persistLayerRemaining();
            $this->applyReturnAdjustments();
            $this->syncProductCosts($scope);
            $this->syncFinancialReports($scope);
        });

        return $this->stats;
    }

    /**
     * Hitung ulang modal untuk produk-produk yang dipakai satu order.
     * Dipanggil setelah order disimpan atau diubah.
     */
    public function rebuildForOrder(int $orderId): array
    {
        $productIds = $this->productIdsOfOrders([$orderId]);

        return $this->rebuild($productIds === [] ? null : $productIds);
    }

    /**
     * Total modal FIFO satu order, dari hasil alokasi terakhir.
     *
     * Retur TIDAK dikurangkan di sini: retur punya baris financial_report
     * sendiri yang membalikkan biayanya. Kalau dikurangkan di dua tempat,
     * COGS-nya terhitung dua kali.
     */
    public function costOfOrder(int $orderId): float
    {
        return (float) DB::table('order_item_costs')
            ->where('order_id', $orderId)
            ->sum('total_cost');
    }

    /**
     * Total modal barang yang diretur, dihitung dari batch yang benar-benar
     * dimakan penjualan aslinya — bukan dari avg_cost saat retur dibuat.
     *
     * Barang rusak ikut dihitung: biayanya tetap harus dibalikkan, yang tidak
     * kembali hanyalah stoknya.
     */
    public function costOfSaleReturn(int $saleReturnId): float
    {
        $total = DB::table('cost_consumptions')
            ->join('sale_return_items', 'sale_return_items.id', '=', 'cost_consumptions.sale_return_item_id')
            ->where('sale_return_items.sale_return_id', $saleReturnId)
            ->whereNull('sale_return_items.deleted_at')
            ->sum('cost_consumptions.subtotal');

        // Baris retur disimpan negatif; yang dipakai pemanggil nilai positif.
        return abs((float) $total);
    }

    // =====================================================================
    // Scope
    // =====================================================================

    /**
     * Perluas daftar produk sampai tertutup.
     *
     * Kalau produk A ikut dalam bundle A+B, maka baris penjualan bundle itu
     * juga harus dihitung ulang, dan itu menuntut batch B ikut dibangun. Kalau
     * B sendiri masih menarik produk lain, putaran diulang. Bila setelah
     * beberapa putaran masih melebar, lebih murah dan lebih aman rebuild penuh.
     *
     * @param  array<int>  $productIds
     * @return array<int>|null null berarti "lakukan rebuild penuh"
     */
    private function expandScope(array $productIds): ?array
    {
        $scope = array_values(array_unique(array_map('intval', $productIds)));

        if ($scope === []) {
            return null;
        }

        for ($round = 0; $round < self::SCOPE_EXPANSION_ROUNDS; $round++) {
            $orderIds = DB::table('order_items')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($scope) {
                    $query->whereIn('product_id', $scope)
                        ->orWhereIn('product_bundle_id', $this->bundlesContaining($scope));
                })
                ->distinct()
                ->pluck('order_id')
                ->all();

            $expanded = array_values(array_unique(array_merge($scope, $this->productIdsOfOrders($orderIds))));

            if (count($expanded) === count($scope)) {
                return $scope;
            }

            $scope = $expanded;
        }

        // Masih melebar: rebuild penuh saja, hasilnya pasti konsisten.
        return null;
    }

    /**
     * @param  array<int>  $productIds
     * @return array<int>
     */
    private function bundlesContaining(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return DB::table('product_bundle_items')
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)
            ->distinct()
            ->pluck('bundle_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Semua produk yang dipakai order tertentu, termasuk isi bundle-nya.
     *
     * @param  array<int>  $orderIds
     * @return array<int>
     */
    private function productIdsOfOrders(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $items = DB::table('order_items')
            ->whereNull('deleted_at')
            ->whereIn('order_id', $orderIds)
            ->select('product_id', 'product_bundle_id')
            ->get();

        $products = [];
        $bundles = [];

        foreach ($items as $item) {
            if ($item->product_id) {
                $products[] = (int) $item->product_id;
            }

            if ($item->product_bundle_id) {
                $bundles[] = (int) $item->product_bundle_id;
            }
        }

        if ($bundles !== []) {
            $components = DB::table('product_bundle_items')
                ->whereNull('deleted_at')
                ->whereIn('bundle_id', array_unique($bundles))
                ->pluck('product_id');

            foreach ($components as $productId) {
                $products[] = (int) $productId;
            }
        }

        return array_values(array_unique($products));
    }

    /**
     * Baris penjualan yang perlu dihitung ulang untuk scope tertentu.
     *
     * @param  array<int>  $scope
     * @return array<int>
     */
    private function orderItemIdsInScope(array $scope): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('order_items.deleted_at')
            ->whereNull('orders.deleted_at')
            ->where('orders.status', self::SALE_ORDER_STATUS)
            ->where(function ($query) use ($scope) {
                $query->whereIn('order_items.product_id', $scope)
                    ->orWhereIn('order_items.product_bundle_id', $this->bundlesContaining($scope));
            })
            ->pluck('order_items.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function clear(?array $scope): void
    {
        if ($scope === null) {
            DB::table('cost_consumptions')->delete();
            DB::table('order_item_costs')->delete();
            DB::table('cost_layers')->delete();

            return;
        }

        DB::table('cost_consumptions')->whereIn('product_id', $scope)->delete();
        DB::table('cost_layers')->whereIn('product_id', $scope)->delete();

        foreach (array_chunk($this->orderItemIdsInScope($scope), 1000) as $chunk) {
            DB::table('order_item_costs')->whereIn('order_item_id', $chunk)->delete();
        }
    }

    // =====================================================================
    // Tahap 1: menyusun batch stok masuk
    // =====================================================================

    private function buildLayers(?array $scope): void
    {
        $rows = [];
        $now = now();

        // --- Opening stock: batch paling awal, harganya opening_rate. ---
        //
        // Tanggalnya mengikuti tanggal mulai pembukuan kalau disetel, supaya
        // stok awal duduk tepat di titik potongnya. Tanpa setelan itu dia
        // memakai tanggal semu paling lampau agar selalu di depan antrian.
        $openingDate = $this->startDate?->toDateTimeString() ?? self::OPENING_DATE;

        foreach ($this->openingStocks($scope) as $productId => $opening) {
            $rows[] = [
                'product_id' => $productId,
                'source_type' => CostLayer::SOURCE_OPENING,
                'source_id' => null,
                'reference' => 'Opening Stock',
                'layer_date' => $openingDate,
                'qty_in' => $opening['qty'],
                'qty_remaining' => $opening['qty'],
                'unit_cost' => $opening['rate'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // --- Pembelian: satu kejadian STOCK IN = satu batch. ---
        //
        // Yang membentuk batch adalah barang yang benar-benar diterima, bukan
        // yang baru dipesan atau baru diketik di Purchase List. Konsekuensinya
        // dua: purchase yang belum stock in tidak ikut menilai penjualan, dan
        // penerimaan bertahap (datang dua kali) jadi dua batch dengan tanggal
        // masing-masing. Harganya tetap dari purchase item asalnya.
        DB::table('inventory_stock_in_histories_2 as h')
            ->join('inventory_stock_ins_2 as si', 'si.id', '=', 'h.inventory_stock_in_id')
            ->join('inventory_items_2 as ii', 'ii.id', '=', 'h.inventory_item_id')
            ->join('purchase_items as pi', 'pi.id', '=', 'ii.purchase_item_id')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNull('h.deleted_at')
            ->whereNull('si.deleted_at')
            ->whereNull('ii.deleted_at')
            ->whereNull('pi.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('pi.status', self::PURCHASE_ITEM_STATUS)
            ->where('h.stock_in', '>', 0)
            ->when($scope !== null, fn ($query) => $query->whereIn('ii.product_id', $scope))
            // Stock in sebelum tanggal mulai pembukuan tidak dibuatkan batch:
            // barangnya sudah terwakili oleh Opening Stock. Kalau tetap ikut,
            // stoknya terhitung dua kali.
            ->when($this->startDate !== null, fn ($query) => $query->whereDate('si.change_date', '>=', $this->startDate))
            ->orderBy('si.change_date')
            ->orderBy('h.id')
            ->select([
                'h.id AS history_id',
                'h.stock_in AS stock_in',
                'si.change_date AS change_date',
                'ii.product_id AS product_id',
                'ii.purchase_item_id AS purchase_item_id',
                'pi.unit_conversion_value AS unit_conversion_value',
                'pi.final_price AS final_price',
                'p.purchase_number AS purchase_number',
                'p.purchase_date AS purchase_date',
            ])
            ->chunk(1000, function ($histories) use (&$rows, $now) {
                foreach ($histories as $history) {
                    $qty = (float) $history->stock_in;

                    if ($qty <= 0) {
                        continue;
                    }

                    $date = $history->change_date
                        ? Carbon::parse($history->change_date)->startOfDay()->toDateTimeString()
                        : ($history->purchase_date ?: self::OPENING_DATE);

                    $rows[] = [
                        'product_id' => (int) $history->product_id,
                        'source_type' => CostLayer::SOURCE_PURCHASE,
                        'source_id' => (int) $history->purchase_item_id,
                        'reference' => $history->purchase_number,
                        'layer_date' => $date,
                        'qty_in' => $qty,
                        'qty_remaining' => $qty,
                        'unit_cost' => $this->baseUnitCost($history->final_price, $history->unit_conversion_value),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            });

        // --- Retur pembelian mengurangi batch purchase item yang sama. ---
        $rows = $this->applyPurchaseReturns($rows);

        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            DB::table('cost_layers')->insert($chunk);
        }

        $this->stats['layers'] = count($rows);
    }

    /**
     * Kurangi batch dengan retur pembelian, dari batch tertua dulu.
     *
     * Satu purchase item bisa punya beberapa batch kalau barangnya datang
     * bertahap, jadi retur dibagi ke batch-batchnya, bukan ke satu baris.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyPurchaseReturns(array $rows): array
    {
        $returned = $this->returnedQtyByPurchaseItem();

        if ($returned === []) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $purchaseItemId = $row['source_id'];

            if ($row['source_type'] !== CostLayer::SOURCE_PURCHASE || empty($returned[$purchaseItemId])) {
                continue;
            }

            $take = min($returned[$purchaseItemId], $rows[$index]['qty_in']);

            $rows[$index]['qty_in'] -= $take;
            $rows[$index]['qty_remaining'] -= $take;
            $returned[$purchaseItemId] -= $take;
        }

        return array_values(array_filter($rows, fn ($row) => $row['qty_in'] > 0));
    }

    /**
     * Stok awal per produk: kuantitas gudang DITAMBAH kuantitas produksi,
     * dinilai dengan opening_rate milik gudang.
     *
     * Harga stok awal hanya dicatat sekali, di inventory_stocks.opening_rate.
     * production_stocks tidak punya kolom harga sama sekali, jadi stok awal
     * produksi dinilai dengan rate yang sama — barangnya memang produk yang
     * sama, hanya beda tempat menyimpannya.
     *
     * @param  array<int>|null  $scope
     * @return array<int, array{qty: float, rate: float}>
     */
    private function openingStocks(?array $scope): array
    {
        $opening = [];

        DB::table('inventory_stocks')
            ->select('product_id', DB::raw('SUM(opening_stock) AS qty'), DB::raw('AVG(opening_rate) AS rate'))
            ->when($scope !== null, fn ($query) => $query->whereIn('product_id', $scope))
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->get()
            ->each(function ($row) use (&$opening) {
                $opening[(int) $row->product_id] = [
                    'qty' => (float) $row->qty,
                    'rate' => (float) $row->rate,
                ];
            });

        DB::table('production_stocks')
            ->select('product_id', DB::raw('SUM(opening_stock) AS qty'))
            ->where('opening_stock', '>', 0)
            ->when($scope !== null, fn ($query) => $query->whereIn('product_id', $scope))
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->get()
            ->each(function ($row) use (&$opening) {
                $productId = (int) $row->product_id;

                $opening[$productId] ??= ['qty' => 0.0, 'rate' => 0.0];
                $opening[$productId]['qty'] += (float) $row->qty;
            });

        // Stok awal yang harganya belum diisi TIDAK dibuatkan batch.
        //
        // Opening rate 0 itu data yang belum diisi, bukan barang gratis. Kalau
        // tetap dijadikan batch, dia duduk paling depan di antrian FIFO dan
        // menelan penjualan-penjualan awal dengan modal nol — dan celakanya
        // tidak ditandai taksiran, karena secara teknis batch-nya "ada".
        // Dilewati saja: penjualannya akan jatuh ke harga taksiran dari batch
        // pembelian yang diketahui, dan ketahuan sebagai baris yang perlu
        // dibereskan.
        $this->openingWithoutRate = [];

        foreach ($opening as $productId => $item) {
            if ($item['qty'] > 0 && $item['rate'] <= 0) {
                $this->openingWithoutRate[] = $productId;
            }
        }

        return array_filter($opening, fn ($item) => $item['qty'] > 0 && $item['rate'] > 0);
    }

    /**
     * Kuantitas retur pembelian per purchase item, dalam satuan dasar.
     *
     * purchase_return_items menyimpan qty dalam satuan beli, jadi dikonversi
     * memakai unit_conversion_value milik purchase item asalnya.
     *
     * @return array<int, float>
     */
    private function returnedQtyByPurchaseItem(): array
    {
        if (! Schema::hasTable('purchase_return_items')) {
            return [];
        }

        $rows = DB::table('purchase_return_items')
            ->join('purchase_items', 'purchase_items.id', '=', 'purchase_return_items.purchase_item_id')
            ->whereNull('purchase_return_items.deleted_at')
            ->groupBy('purchase_return_items.purchase_item_id')
            ->select([
                'purchase_return_items.purchase_item_id AS purchase_item_id',
                DB::raw('SUM(purchase_return_items.quantity * GREATEST(purchase_items.unit_conversion_value, 1)) AS qty'),
            ])
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(int) $row->purchase_item_id] = (float) $row->qty;
        }

        return $result;
    }

    /**
     * Harga per satuan dasar dari harga per satuan beli.
     *
     * Inilah tempat konversi satuan diselesaikan: beli 1 Dus seharga 1.000.000
     * dengan konversi 1.000 menghasilkan modal 1.000 per Pcs.
     */
    private function baseUnitCost($finalPrice, $conversion): float
    {
        $conversion = (float) $conversion;

        if ($conversion <= 0) {
            $conversion = 1.0;
        }

        return round((float) $finalPrice / $conversion, 5);
    }

    // =====================================================================
    // Tahap 2: memuat antrian ke memori
    // =====================================================================

    private function loadQueues(?array $scope): void
    {
        DB::table('cost_layers')
            ->select('id', 'product_id', 'qty_remaining', 'unit_cost', 'layer_date')
            ->when($scope !== null, fn ($query) => $query->whereIn('product_id', $scope))
            ->orderBy('product_id')
            ->orderBy('layer_date')
            ->orderBy('id')
            ->chunk(2000, function ($layers) {
                foreach ($layers as $layer) {
                    $productId = (int) $layer->product_id;

                    $this->queues[$productId][] = [
                        'id' => (int) $layer->id,
                        'qty' => (float) $layer->qty_remaining,
                        'cost' => (float) $layer->unit_cost,
                        'date' => (string) $layer->layer_date,
                    ];

                    $this->firstLayerCost[$productId] ??= (float) $layer->unit_cost;
                }
            });

        foreach (array_keys($this->queues) as $productId) {
            $this->cursors[$productId] = 0;
            $this->available[$productId] = 0;
        }

        // Produk tanpa batch sama sekali (misalnya hasil produksi) tetap butuh
        // taksiran. avg_cost dipakai hanya sebagai cadangan terakhir; nilainya
        // sendiri sudah diisi ulang dari FIFO oleh syncProductCosts().
        DB::table('products')
            ->select('id', 'avg_cost')
            ->where('avg_cost', '>', 0)
            ->when($scope !== null, fn ($query) => $query->whereIn('id', $scope))
            ->orderBy('id')
            ->chunk(2000, function ($products) {
                foreach ($products as $product) {
                    $this->fallbackProductCost[(int) $product->id] = (float) $product->avg_cost;
                }
            });
    }

    // =====================================================================
    // Tahap 3: memutar ulang penjualan dan retur
    // =====================================================================

    private function replayTimeline(?array $scope): void
    {
        $this->loadBundleComponents();

        DB::query()
            ->fromSub($this->timelineQuery($scope), 'e')
            ->orderBy('event_date')
            ->orderBy('kind')
            ->orderBy('ref_id')
            ->chunk(500, function ($events) {
                foreach ($events as $event) {
                    if ($event->kind === 'sale') {
                        $this->allocateSale($event);
                    } else {
                        $this->applyReturn($event);
                    }
                }
            });

        $this->flushConsumptions(true);
        $this->flushOrderCosts(true);
    }

    /**
     * Penjualan dan retur digabung jadi satu garis waktu supaya urutannya
     * benar: stok yang balik dari retur tanggal 5 harus bisa dipakai penjualan
     * tanggal 6, tapi tidak oleh penjualan tanggal 4.
     */
    private function timelineQuery(?array $scope)
    {
        $bundleIds = $scope === null ? [] : $this->bundlesContaining($scope);

        $inScope = function ($query, string $table) use ($scope, $bundleIds) {
            if ($scope === null) {
                return $query;
            }

            return $query->where(function ($q) use ($scope, $bundleIds, $table) {
                $q->whereIn($table.'.product_id', $scope);

                if ($bundleIds !== []) {
                    $q->orWhereIn($table.'.product_bundle_id', $bundleIds);
                }
            });
        };

        $sales = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereNull('oi.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('o.status', self::SALE_ORDER_STATUS)
            ->tap(fn ($query) => $inScope($query, 'oi'))
            // Penjualan sebelum tanggal mulai pembukuan tidak dihitung ulang:
            // stok yang dipakainya sudah habis di periode lama, dan Opening
            // Stock mewakili sisa setelah penjualan-penjualan itu.
            ->when($this->startDate !== null, fn ($query) => $query->where('o.order_date', '>=', $this->startDate))
            ->select([
                DB::raw("'sale' AS kind"),
                'oi.id AS ref_id',
                'o.order_date AS event_date',
                'oi.order_id AS order_id',
                'oi.id AS order_item_id',
                'oi.product_id AS product_id',
                'oi.product_bundle_id AS product_bundle_id',
                'oi.quantity AS quantity',
                'oi.qty_base AS qty_base',
                'oi.unit_conversion_value AS unit_conversion_value',
                'oi.subtotal AS subtotal',
                'oi.total_after_discount AS total_after_discount',
                DB::raw('0 AS canceled_quantity'),
                DB::raw('0 AS defect_quantity'),
            ]);

        if (! Schema::hasTable('sale_return_items')) {
            return $sales;
        }

        $returns = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->join('order_items as oi', 'oi.id', '=', 'sri.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereNull('sri.deleted_at')
            ->whereNull('sr.deleted_at')
            ->whereNull('oi.deleted_at')
            ->whereNull('o.deleted_at')
            ->tap(fn ($query) => $inScope($query, 'oi'))
            // Retur atas penjualan lama ikut dilewati, karena penjualan yang
            // dibalikkannya memang tidak diputar ulang.
            ->when($this->startDate !== null, fn ($query) => $query->where('o.order_date', '>=', $this->startDate))
            ->select([
                DB::raw("'return' AS kind"),
                'sri.id AS ref_id',
                'sr.return_date AS event_date',
                'oi.order_id AS order_id',
                'sri.order_item_id AS order_item_id',
                'oi.product_id AS product_id',
                'oi.product_bundle_id AS product_bundle_id',
                'sri.quantity AS quantity',
                'oi.qty_base AS qty_base',
                'oi.unit_conversion_value AS unit_conversion_value',
                DB::raw('0 AS subtotal'),
                DB::raw('0 AS total_after_discount'),
                'sri.canceled_quantity AS canceled_quantity',
                'sri.defect_quantity AS defect_quantity',
            ]);

        return $sales->unionAll($returns);
    }

    private function loadBundleComponents(): void
    {
        DB::table('product_bundle_items')
            ->whereNull('deleted_at')
            ->select('bundle_id', 'product_id', 'quantity')
            ->orderBy('bundle_id')
            ->chunk(2000, function ($rows) {
                foreach ($rows as $row) {
                    $this->bundleComponents[(int) $row->bundle_id][] = [
                        'product_id' => (int) $row->product_id,
                        'qty' => (float) ($row->quantity ?: 1),
                    ];
                }
            });
    }

    private function allocateSale(object $event): void
    {
        $qtyBase = (float) ($event->qty_base ?: $event->quantity);

        if ($qtyBase <= 0) {
            return;
        }

        // Bundle dipecah jadi komponennya: modal bundle = jumlah modal isinya.
        $demands = [];

        if ($event->product_bundle_id) {
            foreach ($this->bundleComponents[(int) $event->product_bundle_id] ?? [] as $component) {
                $demands[] = [$component['product_id'], $qtyBase * $component['qty']];
            }
        } elseif ($event->product_id) {
            $demands[] = [(int) $event->product_id, $qtyBase];
        }

        if ($demands === []) {
            return;
        }

        $orderItemId = (int) $event->order_item_id;
        $totalCost = 0.0;
        $estimated = false;

        foreach ($demands as [$productId, $demandQty]) {
            [$cost, $isEstimated] = $this->consume($event, $productId, $demandQty);
            $totalCost += $cost;
            $estimated = $estimated || $isEstimated;
        }

        $revenue = (float) ($event->total_after_discount ?: $event->subtotal);

        $this->saleQtyBase[$orderItemId] = $qtyBase;

        $this->orderCostBuffer[] = [
            'order_id' => (int) $event->order_id,
            'order_item_id' => $orderItemId,
            'product_id' => $event->product_id ? (int) $event->product_id : null,
            'product_bundle_id' => $event->product_bundle_id ? (int) $event->product_bundle_id : null,
            'qty_base' => $qtyBase,
            'returned_qty' => 0,
            'total_cost' => round($totalCost, 4),
            'returned_cost' => 0,
            'unit_cost' => round($totalCost / $qtyBase, 5),
            'revenue' => $revenue,
            'margin' => round($revenue - $totalCost, 4),
            'is_estimated' => $estimated,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->stats['order_items']++;

        if ($estimated) {
            $this->stats['estimated_items']++;
        }

        $this->flushOrderCosts();
    }

    /**
     * Makan antrian FIFO satu produk sebanyak $qty.
     *
     * @return array{0:float, 1:bool} total modal, dan apakah ada bagian taksiran
     */
    private function consume(object $event, int $productId, float $qty): array
    {
        $orderItemId = (int) $event->order_item_id;
        $remaining = $qty;
        $total = 0.0;
        $estimated = false;

        if (isset($this->queues[$productId])) {
            $this->advanceAvailability($productId, (string) $event->event_date);

            $limit = $this->available[$productId];
            $queue = &$this->queues[$productId];
            $cursor = $this->cursors[$productId] ?? 0;

            while ($remaining > self::EPSILON && $cursor < $limit && isset($queue[$cursor])) {
                if ($queue[$cursor]['qty'] <= 0) {
                    $cursor++;

                    continue;
                }

                $take = min($queue[$cursor]['qty'], $remaining);
                $unitCost = $queue[$cursor]['cost'];
                $layerId = $queue[$cursor]['id'];

                $queue[$cursor]['qty'] -= $take;
                $this->layerRemaining[$layerId] = $queue[$cursor]['qty'];

                $this->pushConsumption($event, $productId, $layerId, $take, $unitCost);
                $this->rememberAllocation($orderItemId, $productId, $layerId, $cursor, $take, $unitCost);

                $total += $take * $unitCost;
                $remaining -= $take;
            }

            $this->cursors[$productId] = $cursor;
            unset($queue);
        }

        // Batch habis tapi barangnya tetap terjual. Sisanya dinilai dengan harga
        // taksiran, lalu ditandai supaya ketahuan mana yang perlu dikoreksi
        // setelah purchase-nya masuk.
        if ($remaining > self::EPSILON) {
            $fallback = $this->fallbackCost($productId);

            $this->pushConsumption($event, $productId, null, $remaining, $fallback, true);
            $this->rememberAllocation($orderItemId, $productId, null, null, $remaining, $fallback);

            $total += $remaining * $fallback;
            $estimated = true;
        }

        return [$total, $estimated];
    }

    private function rememberAllocation(int $orderItemId, int $productId, ?int $layerId, ?int $index, float $qty, float $cost): void
    {
        $this->saleAllocations[$orderItemId][] = [
            'product' => $productId,
            'layer' => $layerId,
            'index' => $index,
            'qty' => $qty,
            'cost' => $cost,
        ];
    }

    /**
     * Buka batch yang tanggal masuknya sudah lewat pada tanggal penjualan ini.
     *
     * Tanpa batas ini, penjualan tanggal 1 bisa memakan batch yang baru dibeli
     * tanggal 5 — angka export bulan lalu jadi ikut berubah setiap ada
     * pembelian baru.
     */
    private function advanceAvailability(int $productId, string $saleDate): void
    {
        $queue = $this->queues[$productId];
        $index = $this->available[$productId] ?? 0;

        while (isset($queue[$index]) && $queue[$index]['date'] <= $saleDate) {
            $this->lastAvailableCost[$productId] = $queue[$index]['cost'];
            $index++;
        }

        $this->available[$productId] = $index;
    }

    /**
     * Harga taksiran untuk kuantitas yang tidak tertutup batch mana pun:
     * harga batch terakhir yang sudah datang, lalu batch paling awal produk itu
     * (untuk penjualan sebelum pembelian pertama tercatat), lalu avg_cost lama,
     * dan terakhir nol.
     */
    private function fallbackCost(int $productId): float
    {
        return $this->lastAvailableCost[$productId]
            ?? $this->firstLayerCost[$productId]
            ?? $this->fallbackProductCost[$productId]
            ?? 0.0;
    }

    // =====================================================================
    // Retur penjualan
    // =====================================================================

    /**
     * Kembalikan barang retur ke batch asalnya.
     *
     * Porsi retur dihitung dari kuantitas: retur 20% dari sebuah baris
     * penjualan mengembalikan 20% dari tiap batch yang dimakan baris itu, di
     * harga batch masing-masing. Bundle ikut benar dengan sendirinya, karena
     * alokasi aslinya memang sudah per komponen.
     *
     * Hanya kuantitas canceled yang masuk lagi ke antrian; kuantitas defect
     * biayanya tetap dibalikkan tapi barangnya tidak bisa dijual lagi, jadi
     * tidak dikembalikan ke stok.
     */
    private function applyReturn(object $event): void
    {
        $orderItemId = (int) $event->order_item_id;
        $allocations = $this->saleAllocations[$orderItemId] ?? null;
        $soldQty = $this->saleQtyBase[$orderItemId] ?? 0.0;

        if ($allocations === null || $soldQty <= 0) {
            return;
        }

        $conversion = max((float) ($event->unit_conversion_value ?: 1), 1);
        $canceled = (float) ($event->canceled_quantity ?: 0) * $conversion;
        $defect = (float) ($event->defect_quantity ?: 0) * $conversion;
        $returned = $canceled + $defect;

        if ($returned <= self::EPSILON) {
            // Sebagian data hanya mengisi quantity tanpa rincian canceled/defect.
            $returned = (float) ($event->quantity ?: 0) * $conversion;
            $canceled = $returned;
        }

        if ($returned <= self::EPSILON) {
            return;
        }

        $fraction = min($returned / $soldQty, 1.0);
        $restoreShare = $returned > 0 ? $canceled / $returned : 0.0;
        $creditedCost = 0.0;

        foreach ($allocations as $allocation) {
            $qty = $allocation['qty'] * $fraction;

            if ($qty <= self::EPSILON) {
                continue;
            }

            $restoreQty = $qty * $restoreShare;
            $defectQty = $qty - $restoreQty;

            if ($restoreQty > self::EPSILON) {
                $this->restoreToLayer($allocation, $restoreQty);

                $this->pushReturnConsumption($event, $allocation, $restoreQty, false);
            }

            if ($defectQty > self::EPSILON) {
                $this->pushReturnConsumption($event, $allocation, $defectQty, true);
            }

            $creditedCost += $qty * $allocation['cost'];
        }

        $current = $this->returnAdjustments[$orderItemId] ?? ['qty' => 0.0, 'cost' => 0.0];

        $this->returnAdjustments[$orderItemId] = [
            'qty' => $current['qty'] + $returned,
            'cost' => $current['cost'] + $creditedCost,
        ];

        $this->stats['returns']++;
    }

    private function restoreToLayer(array $allocation, float $qty): void
    {
        if ($allocation['layer'] === null || $allocation['index'] === null) {
            return;
        }

        $productId = $allocation['product'];
        $index = $allocation['index'];

        if (! isset($this->queues[$productId][$index])) {
            return;
        }

        $this->queues[$productId][$index]['qty'] += $qty;
        $this->layerRemaining[$allocation['layer']] = $this->queues[$productId][$index]['qty'];

        // Batch ini terisi lagi, jadi penunjuk FIFO harus mundur ke sana supaya
        // penjualan berikutnya memakainya lebih dulu.
        if (($this->cursors[$productId] ?? 0) > $index) {
            $this->cursors[$productId] = $index;
        }
    }

    private function pushReturnConsumption(object $event, array $allocation, float $qty, bool $defect): void
    {
        $this->consumptionBuffer[] = [
            'order_id' => (int) $event->order_id,
            'order_item_id' => (int) $event->order_item_id,
            'sale_return_item_id' => (int) $event->ref_id,
            'product_id' => $allocation['product'],
            'cost_layer_id' => $allocation['layer'],
            'qty' => round(-$qty, 4),
            'unit_cost' => $allocation['cost'],
            'subtotal' => round(-$qty * $allocation['cost'], 4),
            'is_estimated' => $allocation['layer'] === null,
            'is_defect' => $defect,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->stats['consumptions']++;

        $this->flushConsumptions();
    }

    private function pushConsumption(object $event, int $productId, ?int $layerId, float $qty, float $unitCost, bool $estimated = false): void
    {
        $this->consumptionBuffer[] = [
            'order_id' => (int) $event->order_id,
            'order_item_id' => (int) $event->order_item_id,
            'sale_return_item_id' => null,
            'product_id' => $productId,
            'cost_layer_id' => $layerId,
            'qty' => round($qty, 4),
            'unit_cost' => $unitCost,
            'subtotal' => round($qty * $unitCost, 4),
            'is_estimated' => $estimated,
            'is_defect' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->stats['consumptions']++;

        $this->flushConsumptions();
    }

    // =====================================================================
    // Penulisan
    // =====================================================================

    private function flushConsumptions(bool $force = false): void
    {
        if ($this->consumptionBuffer === [] || (! $force && count($this->consumptionBuffer) < self::INSERT_CHUNK)) {
            return;
        }

        DB::table('cost_consumptions')->insert($this->consumptionBuffer);
        $this->consumptionBuffer = [];
    }

    private function flushOrderCosts(bool $force = false): void
    {
        if ($this->orderCostBuffer === [] || (! $force && count($this->orderCostBuffer) < self::INSERT_CHUNK)) {
            return;
        }

        DB::table('order_item_costs')->insert($this->orderCostBuffer);
        $this->orderCostBuffer = [];
    }

    /**
     * Simpan sisa tiap batch. Satu UPDATE ... CASE per 200 layer, bukan satu
     * query per layer: pada data produksi jumlah layer bisa puluhan ribu.
     */
    private function persistLayerRemaining(): void
    {
        foreach (array_chunk($this->layerRemaining, 200, true) as $chunk) {
            $cases = [];
            $ids = [];

            foreach ($chunk as $layerId => $remaining) {
                $cases[] = 'WHEN '.(int) $layerId.' THEN '.number_format(max($remaining, 0), 4, '.', '');
                $ids[] = (int) $layerId;
            }

            DB::table('cost_layers')
                ->whereIn('id', $ids)
                ->update([
                    'qty_remaining' => DB::raw('CASE id '.implode(' ', $cases).' END'),
                ]);
        }
    }

    /** Kurangi baris penjualan yang sebagian barangnya sudah diretur. */
    private function applyReturnAdjustments(): void
    {
        foreach ($this->returnAdjustments as $orderItemId => $adjustment) {
            DB::table('order_item_costs')
                ->where('order_item_id', $orderItemId)
                ->update([
                    'returned_qty' => round($adjustment['qty'], 4),
                    'returned_cost' => round($adjustment['cost'], 4),
                ]);
        }
    }

    /**
     * Isi ulang harga modal produk dari sisa batch FIFO.
     *
     * Kolom products.avg_cost dan inventory_stocks.avg_cost dipertahankan
     * namanya supaya layar dan laporan lama tetap jalan, tapi isinya bukan lagi
     * rata-rata bergerak seumur hidup produk: sekarang nilainya adalah harga
     * rata-rata tertimbang dari stok yang MASIH ada menurut FIFO. Produk yang
     * stoknya habis memakai harga batch terakhir sebagai acuan.
     */
    private function syncProductCosts(?array $scope): void
    {
        $rows = DB::table('cost_layers')
            ->select([
                'product_id',
                DB::raw('SUM(qty_remaining) AS qty'),
                DB::raw('SUM(qty_remaining * unit_cost) AS value'),
            ])
            ->when($scope !== null, fn ($query) => $query->whereIn('product_id', $scope))
            ->groupBy('product_id')
            ->get();

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $qty = (float) $row->qty;

            $cost = $qty > 0
                ? round((float) $row->value / $qty, 2)
                : round($this->lastLayerCost($productId), 2);

            DB::table('products')->where('id', $productId)->update(['avg_cost' => $cost]);
            DB::table('inventory_stocks')->where('product_id', $productId)->update(['avg_cost' => $cost]);
        }
    }

    /**
     * Tulis ulang COGS di financial_reports dari hasil FIFO.
     *
     * Tanpa langkah ini, export Sale List dan halaman Profit & Loss bisa
     * menunjukkan harga modal yang berbeda untuk transaksi yang sama: yang satu
     * membaca alokasi FIFO, yang satu masih menyimpan angka avg_cost lama dari
     * saat order disimpan.
     *
     * Baris penjualan dan baris retur sama-sama memakai bentuk
     * gross_profit = revenue - cogs. Pada retur, revenue dan cogs sudah
     * tersimpan negatif, jadi rumusnya tidak perlu dibedakan.
     */
    private function syncFinancialReports(?array $scope): void
    {
        $orderFilter = '';
        $bindings = [];

        if ($scope !== null) {
            $orderIds = DB::table('order_item_costs')
                ->whereIn('order_item_id', $this->orderItemIdsInScope($scope))
                ->distinct()
                ->pluck('order_id')
                ->all();

            if ($orderIds === []) {
                return;
            }

            $orderFilter = ' AND fr.reference_id IN ('.implode(',', array_map('intval', $orderIds)).')';
        }

        DB::statement(
            'UPDATE financial_reports fr
             JOIN (
                 SELECT order_id, SUM(total_cost) AS cost
                 FROM order_item_costs
                 GROUP BY order_id
             ) t ON t.order_id = fr.reference_id
             SET fr.cogs = t.cost,
                 fr.gross_profit = fr.revenue - t.cost,
                 fr.net_profit = fr.revenue - t.cost - COALESCE(fr.expense, 0)
             WHERE fr.transaction_type = \'sale\'
               AND fr.reference_table = \'orders\'
               AND fr.deleted_at IS NULL'.$orderFilter,
            $bindings
        );

        if (! Schema::hasTable('sale_return_items')) {
            return;
        }

        // Retur: biayanya diambil dari baris konsumsi negatif, yang nilainya
        // memakai harga batch asli penjualannya.
        DB::statement(
            'UPDATE financial_reports fr
             JOIN (
                 SELECT sri.sale_return_id, SUM(cc.subtotal) AS cost
                 FROM cost_consumptions cc
                 JOIN sale_return_items sri ON sri.id = cc.sale_return_item_id
                 WHERE sri.deleted_at IS NULL
                 GROUP BY sri.sale_return_id
             ) t ON t.sale_return_id = fr.reference_id
             SET fr.cogs = t.cost,
                 fr.gross_profit = fr.revenue - t.cost,
                 fr.net_profit = fr.revenue - t.cost - COALESCE(fr.expense, 0)
             WHERE fr.transaction_type = \'sale_return\'
               AND fr.reference_table = \'sale_returns\'
               AND fr.deleted_at IS NULL'
        );
    }

    /**
     * Produk yang punya stok awal tapi Opening Rate-nya masih 0, sehingga stok
     * awalnya tidak bisa dinilai. Ini penyebab paling sering harga modal jadi
     * taksiran di sistem yang stok awalnya besar.
     *
     * @return array<int, string>
     */
    public function productsWithoutOpeningRate(): array
    {
        if ($this->openingWithoutRate === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $this->openingWithoutRate)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Produk yang punya penjualan tapi tidak punya batch pembelian sama sekali.
     * Modalnya pasti taksiran; daftar ini yang perlu dibereskan datanya.
     *
     * @return array<int, string>
     */
    public function productsWithoutLayers(): array
    {
        return DB::table('cost_consumptions')
            ->join('products', 'products.id', '=', 'cost_consumptions.product_id')
            ->whereNull('cost_consumptions.cost_layer_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('cost_layers')
                    ->whereColumn('cost_layers.product_id', 'cost_consumptions.product_id');
            })
            ->distinct()
            ->pluck('products.name', 'products.id')
            ->all();
    }

    private function lastLayerCost(int $productId): float
    {
        $queue = $this->queues[$productId] ?? [];

        if ($queue === []) {
            return (float) (DB::table('cost_layers')
                ->where('product_id', $productId)
                ->orderByDesc('layer_date')
                ->orderByDesc('id')
                ->value('unit_cost') ?? 0);
        }

        return (float) end($queue)['cost'];
    }

    /** Kapan alokasi terakhir dibangun, untuk ditampilkan di layar bila perlu. */
    public static function lastRebuiltAt(): ?Carbon
    {
        $value = DB::table('order_item_costs')->max('updated_at');

        return $value ? Carbon::parse($value) : null;
    }
}
