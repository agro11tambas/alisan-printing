<?php

namespace App\Console\Commands;

use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairProductionAvailableStock extends Command
{
    protected $signature = 'stock:repair-available
                            {--product= : Batasi ke satu product_id}
                            {--dry-run : Cuma tampilkan selisih, tanpa menyimpan}';

    protected $description = 'Samakan production_stocks.available_quantity dengan closing_stock snapshot hari ini (snapshot dihitung dari assign/opname/stock in, jadi dia yang benar)';

    public function handle(): int
    {
        $today = today()->toDateString();
        $productOption = $this->option('product');
        $dryRun = (bool) $this->option('dry-run');

        $stocks = ProductionStock::query()
            ->when($productOption, fn ($q) => $q->where('product_id', (int) $productOption))
            ->with('product:id,name')
            ->get();

        $rows = [];

        foreach ($stocks as $stock) {
            $closing = ProductionStockSnapshot::where('product_id', $stock->product_id)
                ->whereDate('snapshot_date', $today)
                ->value('closing_stock');

            // Tanpa snapshot hari ini tidak ada acuan yang bisa dipercaya — lewati.
            if ($closing === null) {
                continue;
            }

            $available = (int) $stock->available_quantity;
            $closing = (int) $closing;

            if ($available === $closing) {
                continue;
            }

            $rows[] = [
                $stock->product_id,
                $stock->product?->name ?? '-',
                $available,
                $closing,
                $closing - $available,
            ];

            if (! $dryRun) {
                $stock->update(['available_quantity' => $closing]);
            }
        }

        if (empty($rows)) {
            $this->info("Semua available_quantity sudah sama dengan closing snapshot {$today}.");

            return self::SUCCESS;
        }

        $this->table(['product_id', 'produk', 'available (lama)', 'closing snapshot', 'selisih'], $rows);
        $this->info(($dryRun ? '[dry-run] ' : '').count($rows)." produk ".($dryRun ? 'akan' : 'sudah')." disamakan dengan snapshot {$today}.");

        if (! $dryRun) {
            Log::info('[stock:repair-available] available_quantity disamakan dengan snapshot', ['date' => $today, 'rows' => $rows]);
        }

        return self::SUCCESS;
    }
}
