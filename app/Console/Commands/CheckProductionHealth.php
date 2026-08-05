<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Cek setelan yang membuat ERP lambat di produksi.
 *
 * Jalankan di server setelah deploy:
 *   php artisan app:check-production
 */
class CheckProductionHealth extends Command
{
    protected $signature = 'app:check-production';

    protected $description = 'Cek setelan produksi yang biasanya bikin ERP lambat (debug mode, debugbar, cache, log)';

    public function handle(): int
    {
        $problems = 0;

        $problems += $this->check(
            'APP_DEBUG mati',
            config('app.debug') === false,
            'APP_DEBUG=true membuat Laravel menyimpan seluruh query dan stack trace di memori tiap request. Set APP_DEBUG=false.'
        );

        $problems += $this->check(
            'APP_ENV = production',
            app()->environment('production'),
            'APP_ENV sekarang "'.app()->environment().'". Set APP_ENV=production supaya optimasi framework aktif.'
        );

        $problems += $this->check(
            'Debugbar mati',
            config('debugbar.enabled') === false,
            'Debugbar merekam tiap query dan menulis file JSON ratusan KB per request ke storage/debugbar. Set DEBUGBAR_ENABLED=false.'
        );

        $problems += $this->check(
            'Level log bukan debug',
            ! in_array(config('logging.channels.single.level'), ['debug', 'info'], true),
            'LOG_LEVEL=debug menulis ribuan baris per hari ke satu file. Set LOG_LEVEL=error.'
        );

        $problems += $this->check(
            'Catatan performa tidak ikut LOG_LEVEL',
            config('logging.channels.performance.level') === 'debug',
            'Channel "performance" harus level debug. Kalau ikut LOG_LEVEL=error, catatan request lambat dibuang diam-diam.'
        );

        // Pencegahan, bukan masalah aktif: selama laravel.log masih kecil,
        // rotasi belum mendesak. Ukuran storage/logs dilaporkan di bawah.
        $this->advise(
            'Log aplikasi dirotasi',
            in_array('daily', explode(',', (string) env('LOG_STACK', 'single')), true),
            'LOG_STACK=single tidak pernah dirotasi. Pakai LOG_STACK=daily dan LOG_DAILY_DAYS=14 sebelum filenya membesar.'
        );

        $problems += $this->reportSessionTable();
        $problems += $this->checkPendingMigrations();

        $problems += $this->check(
            'Config ter-cache',
            File::exists($this->laravel->getCachedConfigPath()),
            'Jalankan: php artisan config:cache'
        );

        $problems += $this->check(
            'Route ter-cache',
            File::exists($this->laravel->getCachedRoutesPath()),
            'Jalankan: php artisan route:cache'
        );

        $problems += $this->check(
            'Event ter-cache',
            File::exists($this->laravel->getCachedEventsPath()),
            'Jalankan: php artisan event:cache'
        );

        $problems += $this->reportDirectorySize(
            'storage/debugbar',
            storage_path('debugbar'),
            'Sisa dump Debugbar. Aman dihapus setelah Debugbar dimatikan.'
        );

        $problems += $this->reportDirectorySize(
            'storage/logs',
            storage_path('logs'),
            'Pakai LOG_STACK=daily + LOG_DAILY_DAYS=14 supaya log dirotasi otomatis.'
        );

        $this->newLine();

        if ($problems === 0) {
            $this->info('Semua setelan produksi sudah benar.');

            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$problems} setelan yang perlu diperbaiki.");

        return self::FAILURE;
    }

    /**
     * SESSION_DRIVER=database berarti setiap request membaca dan menulis satu
     * baris di tabel sessions. Laravel baru menghapus sesi lama setelah lewat
     * SESSION_LIFETIME, jadi lifetime yang sangat panjang membuat tabel itu
     * tidak pernah dibersihkan dan terus tumbuh.
     */
    private function reportSessionTable(): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        $lifetimeDays = round(((int) config('session.lifetime')) / 1440);
        $table = config('session.table', 'sessions');

        try {
            $rows = DB::table($table)->count();
        } catch (\Throwable) {
            $this->line("  <fg=yellow>LEWAT</> tabel {$table} tidak bisa dibaca");

            return 0;
        }

        // Yang membebani adalah jumlah barisnya, bukan panjang lifetime-nya.
        // Lifetime panjang hanya berarti pembersihan otomatis tidak jalan, dan
        // itu tidak jadi masalah selama tabelnya masih kecil.
        if ($rows < 25_000) {
            $this->line("  <fg=green>OK</>    tabel {$table} ({$rows} baris, kedaluwarsa {$lifetimeDays} hari)");

            return 0;
        }

        $this->line("  <fg=yellow>BESAR</> tabel {$table} ({$rows} baris, kedaluwarsa {$lifetimeDays} hari)");
        $this->line('          → Setiap request membaca dan menulis tabel ini. Turunkan SESSION_LIFETIME supaya');
        $this->line('            Laravel membersihkan sendiri, atau hapus baris lama secara manual.');

        return 1;
    }

    /**
     * Index dan perubahan skema hanya berguna kalau migrasinya benar-benar
     * jalan di server. Deploy yang gagal di langkah migrate mudah terlewat
     * karena aplikasinya tetap hidup, cuma tetap lambat.
     */
    private function checkPendingMigrations(): int
    {
        $migrator = $this->laravel->make('migrator');

        try {
            if (! $migrator->repositoryExists()) {
                $this->line('  <fg=red>MASALAH</> Migrasi belum pernah dijalankan');
                $this->line('          → Jalankan: php artisan migrate --force');

                return 1;
            }

            $files = $migrator->getMigrationFiles($this->laravel->databasePath('migrations'));
            $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());
        } catch (\Throwable $e) {
            $this->line('  <fg=yellow>LEWAT</> status migrasi tidak bisa dibaca: '.$e->getMessage());

            return 0;
        }

        if ($pending === []) {
            $this->line('  <fg=green>OK</>    Tidak ada migrasi tertunda');

            return 0;
        }

        $this->line('  <fg=red>MASALAH</> '.count($pending).' migrasi belum dijalankan');
        foreach (array_slice($pending, 0, 5) as $migration) {
            $this->line("          · {$migration}");
        }
        $this->line('          → Jalankan: php artisan migrate --force');

        return 1;
    }

    /**
     * Saran yang tidak dihitung sebagai masalah, supaya ringkasan di akhir
     * hanya memuat hal yang benar-benar perlu ditindak sekarang.
     */
    private function advise(string $label, bool $passed, string $suggestion): void
    {
        if ($passed) {
            $this->line("  <fg=green>OK</>    {$label}");

            return;
        }

        $this->line("  <fg=cyan>SARAN</> {$label}");
        $this->line("          → {$suggestion}");
    }

    private function check(string $label, bool $passed, string $fix): int
    {
        if ($passed) {
            $this->line("  <fg=green>OK</>    {$label}");

            return 0;
        }

        $this->line("  <fg=red>MASALAH</> {$label}");
        $this->line("          → {$fix}");

        return 1;
    }

    /**
     * Direktori yang membengkak bukan error, tapi ikut memperlambat disk I/O
     * di shared hosting, jadi tetap dilaporkan kalau lewat 50 MB.
     */
    private function reportDirectorySize(string $label, string $path, string $fix): int
    {
        if (! File::isDirectory($path)) {
            return 0;
        }

        $bytes = collect(File::allFiles($path))->sum(fn ($file) => $file->getSize());
        $megabytes = round($bytes / 1_048_576, 1);

        if ($megabytes < 50) {
            $this->line("  <fg=green>OK</>    {$label} ({$megabytes} MB)");

            return 0;
        }

        $this->line("  <fg=yellow>BESAR</> {$label} ({$megabytes} MB)");
        $this->line("          → {$fix}");

        return 1;
    }
}
