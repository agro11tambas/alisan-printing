<?php

namespace App\Console\Commands;

use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TakeProductionStockSnapshot extends Command
{
    protected $signature = 'stock:snapshot';

    protected $description = 'Take a daily opening stock snapshot for all active production stocks';

    public function handle(): void
    {
        $today = today()->toDateString();
        $stocks = ProductionStock::query()
            ->whereHas('product', fn ($q) => $q->whereNull('products.deleted_at'))
            ->get();

        foreach ($stocks as $stock) {
            $productId = $stock->product_id;

            $snapshot = ProductionStockSnapshot::firstOrNew([
                'product_id' => $productId,
                'snapshot_date' => $today,
            ]);

            // opening_stock hanya diset saat pertama kali (record baru) dan
            // diambil dari closing_stock hari sebelumnya. Setelah itu tidak pernah berubah,
            // berapa kali pun command ini jalan di hari yang sama.
            if (! $snapshot->exists) {
                $snapshot->opening_stock = ProductionStockSnapshot::resolveOpeningStock($productId, $today);
            }

            $snapshot->stock_in_today = ProductionStockSnapshot::stockInTodayFor($productId, $today);
            $snapshot->assign_today = ProductionStockSnapshot::assignTodayFor($productId, $today);
            $snapshot->stock_opname_today = ProductionStockSnapshot::stockOpnameTodayFor($productId, $today);

            // closing_stock selalu dihitung ulang: opening - assign today + stock in today + stock opname today
            $snapshot->closing_stock = ProductionStockSnapshot::calculateClosingStock(
                (int) $snapshot->opening_stock,
                (int) $snapshot->assign_today,
                (int) $snapshot->stock_in_today,
                (int) $snapshot->stock_opname_today,
            );

            $snapshot->save();
        }

        $this->info("Snapshot done for {$today}.");
        Log::info("[stock:snapshot] Snapshot saved for {$today}.");
    }
}
