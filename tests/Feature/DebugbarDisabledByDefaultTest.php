<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Debugbar aktif di produksi pada 5 Agustus 2026 karena config bawaan paket
 * memakai `env('DEBUGBAR_ENABLED', null)`, yang artinya "ikut APP_DEBUG".
 * Server produksi menjalankan APP_DEBUG=true, jadi setiap request diprofil
 * dan ditulis sebagai file JSON ratusan KB ke storage/debugbar.
 *
 * config/debugbar.php sekarang default-nya false. Test ini menjaga supaya
 * default itu tidak diam-diam kembali mengikuti APP_DEBUG.
 */
class DebugbarDisabledByDefaultTest extends TestCase
{
    public function test_debugbar_is_off_unless_turned_on_explicitly(): void
    {
        $this->assertFalse(
            config('debugbar.enabled'),
            'Debugbar harus mati kecuali DEBUGBAR_ENABLED=true di-set manual.'
        );
    }

    public function test_debugbar_does_not_follow_app_debug(): void
    {
        config(['app.debug' => true]);

        $this->assertNotNull(
            config('debugbar.enabled'),
            'config debugbar.enabled bernilai null berarti Debugbar ikut APP_DEBUG lagi.'
        );
    }
}
