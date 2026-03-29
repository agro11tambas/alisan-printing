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

    // public function handle(): void
    // {
    //     $today  = today()->toDateString();
    //     $stocks = ProductionStock::query()
    //         ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
    //         ->get();

    //     $inserted = 0;
    //     $skipped  = 0;

    //     foreach ($stocks as $stock) {
    //         $exists = ProductionStockSnapshot::where('product_id', $stock->product_id)
    //             ->whereDate('snapshot_date', $today)
    //             ->exists();

    //         if ($exists) {
    //             $skipped++;
    //             continue;
    //         }

    //         ProductionStockSnapshot::create([
    //             'product_id'    => $stock->product_id,
    //             'opening_stock' => $stock->available_quantity ?? 0,
    //             'snapshot_date' => $today,
    //         ]);

    //         $inserted++;
    //     }

    //     $this->info("Snapshot done: {$inserted} inserted, {$skipped} skipped.");
    //     Log::info("[stock:snapshot] {$inserted} inserted, {$skipped} skipped for {$today}.");
    // }

    public function handle(): void
    {
        $today  = today()->toDateString();
        $stocks = ProductionStock::query()
            ->whereHas('product', fn($q) => $q->whereNull('products.deleted_at'))
            ->get();

        foreach ($stocks as $stock) {
            $productId = $stock->product_id;

            $snapshot = ProductionStockSnapshot::firstOrNew([
                'product_id'    => $productId,
                'snapshot_date' => $today,
            ]);

            // opening_stock hanya diset saat pertama kali (record baru)
            if (!$snapshot->exists) {
                $snapshot->opening_stock = $stock->available_quantity ?? 0;
            }

            // closing_stock selalu update = stok real-time saat command jalan
            $snapshot->closing_stock = $stock->available_quantity ?? 0;

            // stock_in_today
            $fromMaterial = DB::table('material_request_items')
                ->where('product_id', $productId)->whereNull('deleted_at')
                ->whereDate('created_at', $today)->sum('received_qty');

            $fromInventory = DB::table('inventory_items_2')
                ->where('product_id', $productId)->whereNull('deleted_at')
                ->whereNotNull('purchase_item_id')->whereNotNull('production_warehouse_id')
                ->whereDate('created_at', $today)->sum('stock_in');

            $snapshot->stock_in_today = ($fromMaterial ?? 0) + ($fromInventory ?? 0);

            // assign_today
            $snapshot->assign_today = \App\Models\OrderProgressAssign::where('product_id', $productId)
                ->whereNull('deleted_at')
                ->whereDate('created_at', $today)
                ->sum('assigned_quantity');

            $snapshot->save();
        }

        $this->info("Snapshot done for {$today}.");
        Log::info("[stock:snapshot] Snapshot saved for {$today}.");
    }
}
