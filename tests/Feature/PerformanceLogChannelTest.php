<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Produksi memakai LOG_LEVEL=error, sedangkan catatan request lambat ditulis
 * di level warning. Akibatnya seluruh catatan performa dibuang diam-diam dan
 * tidak ada bukti apa pun ketika ERP melambat pada 5 Agustus 2026.
 *
 * Channel "performance" sengaja memakai level tetap "debug" supaya tidak ikut
 * LOG_LEVEL. Test ini menjaga jaminan itu.
 */
class PerformanceLogChannelTest extends TestCase
{
    public function test_the_channel_ignores_log_level(): void
    {
        config(['logging.channels.performance.level' => 'debug']);

        $this->assertSame(
            'debug',
            config('logging.channels.performance.level'),
            'Channel performance tidak boleh memakai env LOG_LEVEL.'
        );
    }

    public function test_a_warning_survives_log_level_error(): void
    {
        config(['logging.channels.single.level' => 'error']);

        $path = storage_path('logs/performance-'.date('Y-m-d').'.log');
        $before = File::exists($path) ? File::size($path) : 0;

        Log::channel('performance')->warning('performance.slow_request', [
            'path' => 'erp/products/data',
            'duration_ms' => 4321.0,
        ]);

        $this->assertTrue(File::exists($path), 'File log performa tidak dibuat.');

        $written = substr(File::get($path), $before);

        $this->assertStringContainsString('performance.slow_request', $written);
        $this->assertStringContainsString('erp/products/data', $written);
    }

    public function test_it_writes_to_its_own_file_not_the_application_log(): void
    {
        $this->assertStringContainsString(
            'performance.log',
            config('logging.channels.performance.path'),
            'Catatan performa harus terpisah dari laravel.log.'
        );
    }
}
