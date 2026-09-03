<?php

namespace App\Console\Commands;

use App\Services\FifoCostService;
use Illuminate\Console\Command;

/**
 * Bangun ulang seluruh cost layer FIFO dan alokasinya ke tiap baris penjualan.
 *
 * Dijalankan manual setelah data purchase/penjualan berubah, atau dijadwalkan
 * harian. Selama modul ini belum dipasang ke alur simpan transaksi, perintah
 * inilah satu-satunya yang mengisi tabel order_item_costs yang dibaca export.
 */
class RebuildFifoCost extends Command
{
    protected $signature = 'cost:rebuild-fifo';

    protected $description = 'Hitung ulang harga modal FIFO per baris penjualan dari seluruh riwayat pembelian';

    public function handle(FifoCostService $service): int
    {
        $this->info('Membangun ulang cost layer FIFO...');

        $started = microtime(true);
        $stats = $service->rebuild();
        $elapsed = round(microtime(true) - $started, 2);

        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Batch pembelian (cost_layers)', number_format($stats['layers'])],
                ['Baris penjualan dihitung', number_format($stats['order_items'])],
                ['Alokasi batch (cost_consumptions)', number_format($stats['consumptions'])],
                ['Retur penjualan dikembalikan ke batch', number_format($stats['returns'])],
                ['Baris dengan modal taksiran', number_format($stats['estimated_items'])],
            ]
        );

        if ($stats['estimated_items'] > 0) {
            $this->warn(
                $stats['estimated_items'].' baris penjualan memakai harga taksiran karena '.
                'batch pembeliannya tidak mencukupi. Rinciannya ada di tabel cost_consumptions '.
                '(is_estimated = 1).'
            );
        }

        // Produk yang dijual tapi tidak pernah punya pembelian sama sekali:
        // modalnya tidak bisa dihitung dari mana pun, hanya bisa ditaksir.
        // Ini masalah data master, bukan masalah hitungan.
        $orphans = $service->productsWithoutLayers();

        if ($orphans !== []) {
            $this->newLine();
            $this->warn('Produk berikut terjual tapi tidak punya batch pembelian sama sekali, jadi modalnya nol/taksiran:');

            foreach ($orphans as $id => $name) {
                $this->line('  - ['.$id.'] '.$name);
            }
        }

        $this->info('Selesai dalam '.$elapsed.' detik.');

        return self::SUCCESS;
    }
}
