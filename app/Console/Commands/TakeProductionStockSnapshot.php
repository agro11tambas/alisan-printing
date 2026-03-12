<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TakeProductionStockSnapshot extends Command
{
    protected $signature   = 'stock:snapshot';
    protected $description = 'Take a daily opening stock snapshot for all active production stocks';

    public function handle(): void
    {
        $today  = today()->toDateString();
        $stocks = ProductionStock::query()
            ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
            ->get();

        $inserted = 0;
        $skipped  = 0;

        foreach ($stocks as $stock) {
            $exists = ProductionStockSnapshot::where('product_id', $stock->product_id)
                ->whereDate('snapshot_date', $today)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            ProductionStockSnapshot::create([
                'product_id'    => $stock->product_id,
                'opening_stock' => $stock->available_quantity ?? 0,
                'snapshot_date' => $today,
            ]);

            $inserted++;
        }

        $this->info("Snapshot done: {$inserted} inserted, {$skipped} skipped.");
        Log::info("[stock:snapshot] {$inserted} inserted, {$skipped} skipped for {$today}.");
    }
}
