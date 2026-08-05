<?php

namespace App\Console\Commands;

use App\Models\DeliveryList;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * Ukur biaya query halaman daftar yang paling sering dipakai.
 *
 *   php artisan app:benchmark-lists
 *
 * Ini mengukur bagian yang mahal: query ke database plus hydration model,
 * persis seperti yang dilakukan endpoint /data. Yang TIDAK diukur adalah
 * render Blade dan waktu kirim response, jadi angka di sini selalu lebih
 * kecil dari yang terlihat di browser. Gunanya untuk memisahkan "database
 * lambat" dari "lambat di tempat lain".
 */
class BenchmarkListQueries extends Command
{
    protected $signature = 'app:benchmark-lists {--rows=15 : Jumlah baris per halaman, samakan dengan halaman aslinya}';

    protected $description = 'Ukur waktu dan jumlah query halaman daftar utama (produk, pengiriman, penjualan)';

    public function handle(): int
    {
        $rows = max(1, (int) $this->option('rows'));

        $this->line('Mengukur '.$rows.' baris per daftar. Angka di bawah belum termasuk render halaman.');
        $this->newLine();

        $results = [
            $this->measure('Produk', fn () => Products::with([
                'categories' => fn ($query) => $query->without('discounts'),
                'tags',
                'inventoryStock',
                'unitConversions.unit',
                'unitConversions.prices.priceMode',
                'baseUnit',
                'saleUnit',
                'purchaseUnit',
            ])->orderBy('name', 'asc'), $rows),

            $this->measure('Delivery List', fn () => DeliveryList::with([
                'deliveryOrder.order.customer',
                'deliveryOrder.order.customerAddress',
                'deliveryOrder',
                'items.product',
                'items.deliveryOrderItem',
            ])->orderBy('shipment_date', 'desc'), $rows),

            $this->measure('Delivery Orders', fn () => DeliveryOrder::with([
                'order.customer',
                'order.customerAddress',
                'items.product',
                'items.orderProgress.items',
                'items.deliveryListItems.shipment',
            ])->orderByDesc('id'), $rows),

            $this->measure('Sale List', fn () => Order::with([
                'customer',
                'customerAccount',
                'user',
                'customerAddress',
                'saleReturns',
                'orderItems.product',
                'orderItems.productBundle.items.product',
                'deliveryOrders.items.deliveryListItems.shipment',
                'deliveryOrders.items.deliveryListItems.deliveryOrderItem',
            ])->where('status', 'sale list')->orderBy('order_date', 'desc'), $rows),
        ];

        $this->table(
            ['Daftar', 'Waktu', 'Query', 'Waktu DB', 'Baris'],
            array_map(fn (array $r) => [
                $r['label'],
                $this->formatMs($r['duration_ms']),
                $r['queries'],
                $this->formatMs($r['database_ms']),
                $r['rows'],
            ], $results)
        );

        $slow = array_filter($results, fn (array $r) => $r['duration_ms'] >= 1000);

        $this->newLine();

        if ($slow === []) {
            $this->info('Semua daftar di bawah 1 detik. Kalau di browser masih terasa lambat,');
            $this->info('waktunya habis di luar database: render halaman, jaringan, atau antre proses PHP.');

            return self::SUCCESS;
        }

        foreach ($slow as $result) {
            $this->warn(sprintf(
                '%s: %s dengan %d query.%s',
                $result['label'],
                $this->formatMs($result['duration_ms']),
                $result['queries'],
                $result['queries'] > 60
                    ? ' Jumlah query tinggi, kemungkinan masih ada relasi yang belum di-eager-load.'
                    : ' Query sedikit tapi lambat, periksa EXPLAIN untuk index yang kurang.'
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @param  callable(): Builder  $build
     * @return array{label: string, duration_ms: float, database_ms: float, queries: int, rows: int}
     */
    private function measure(string $label, callable $build, int $rows): array
    {
        $queries = 0;
        $databaseMs = 0.0;

        $listener = function (QueryExecuted $query) use (&$queries, &$databaseMs): void {
            $queries++;
            $databaseMs += $query->time;
        };

        DB::listen($listener);

        $startedAt = hrtime(true);

        try {
            $loaded = $build()->take($rows + 1)->get();
        } catch (\Throwable $e) {
            $this->error("{$label}: gagal diukur — ".$e->getMessage());

            $loaded = collect();
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        return [
            'label' => $label,
            'duration_ms' => $durationMs,
            'database_ms' => $databaseMs,
            'queries' => $queries,
            'rows' => $loaded->count(),
        ];
    }

    private function formatMs(float $ms): string
    {
        return $ms >= 1000
            ? number_format($ms / 1000, 2, ',', '.').' s'
            : number_format($ms, 0, ',', '.').' ms';
    }
}
