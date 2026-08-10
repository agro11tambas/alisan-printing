<?php

namespace App\Console\Commands;

use App\Models\ProductionStockSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairProductionStockSnapshot extends Command
{
    protected $signature = 'stock:snapshot-repair
                            {--from= : Hitung ulang mulai tanggal ini (Y-m-d). Default: semua snapshot}
                            {--product= : Batasi ke satu product_id}
                            {--dry-run : Cuma tampilkan yang bakal berubah, tanpa menyimpan}';

    protected $description = 'Hitung ulang stock_in_today/assign_today/stock_opname_today dari tabel sumber, lalu sambung ulang rantai opening/closing snapshot';

    public function handle(): int
    {
        $from = $this->option('from');
        $productOption = $this->option('product');
        $dryRun = (bool) $this->option('dry-run');

        $productIds = ProductionStockSnapshot::query()
            ->when($productOption, fn ($q) => $q->where('product_id', (int) $productOption))
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id');

        $changed = [];

        foreach ($productIds as $productId) {
            $snapshots = ProductionStockSnapshot::where('product_id', $productId)
                ->when($from, fn ($q) => $q->whereDate('snapshot_date', '>=', $from))
                ->orderBy('snapshot_date')
                ->get();

            if ($snapshots->isEmpty()) {
                continue;
            }

            // Closing terakhir sebelum rentang yang diperbaiki dipakai sebagai jangkar.
            $previousClosing = ProductionStockSnapshot::where('product_id', $productId)
                ->whereDate('snapshot_date', '<', $snapshots->first()->snapshot_date)
                ->orderByDesc('snapshot_date')
                ->value('closing_stock');

            foreach ($snapshots as $snapshot) {
                $date = $snapshot->snapshot_date;

                $before = [
                    'opening' => (int) $snapshot->opening_stock,
                    'closing' => (int) $snapshot->closing_stock,
                    'in' => (int) $snapshot->stock_in_today,
                    'assign' => (int) $snapshot->assign_today,
                    'opname' => (int) $snapshot->stock_opname_today,
                ];

                if ($previousClosing !== null) {
                    $snapshot->opening_stock = (int) $previousClosing;
                }

                $snapshot->stock_in_today = ProductionStockSnapshot::stockInTodayFor($productId, $date);
                $snapshot->assign_today = ProductionStockSnapshot::assignTodayFor($productId, $date);
                $snapshot->stock_opname_today = ProductionStockSnapshot::stockOpnameTodayFor($productId, $date);

                $snapshot->closing_stock = ProductionStockSnapshot::calculateClosingStock(
                    (int) $snapshot->opening_stock,
                    (int) $snapshot->assign_today,
                    (int) $snapshot->stock_in_today,
                    (int) $snapshot->stock_opname_today,
                );

                $after = [
                    'opening' => (int) $snapshot->opening_stock,
                    'closing' => (int) $snapshot->closing_stock,
                    'in' => (int) $snapshot->stock_in_today,
                    'assign' => (int) $snapshot->assign_today,
                    'opname' => (int) $snapshot->stock_opname_today,
                ];

                if ($before !== $after) {
                    $changed[] = [
                        $snapshot->id,
                        $productId,
                        $date->toDateString(),
                        $before['opening'].' → '.$after['opening'],
                        $before['closing'].' → '.$after['closing'],
                        $before['opname'].' → '.$after['opname'],
                    ];

                    if (! $dryRun) {
                        $snapshot->save();
                    }
                }

                $previousClosing = (int) $snapshot->closing_stock;
            }
        }

        if ($changed === []) {
            $this->info('Semua snapshot sudah konsisten, tidak ada yang diubah.');

            return self::SUCCESS;
        }

        $this->table(['id', 'product_id', 'tanggal', 'opening', 'closing', 'opname'], $changed);

        if ($dryRun) {
            $this->warn(count($changed).' baris akan berubah (dry-run, belum disimpan).');

            return self::SUCCESS;
        }

        $this->info(count($changed).' baris snapshot diperbaiki.');
        Log::info('[stock:snapshot-repair] '.count($changed).' baris snapshot diperbaiki.');

        return self::SUCCESS;
    }
}
