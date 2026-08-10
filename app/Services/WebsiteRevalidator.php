<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Memberi tahu website Next.js bahwa katalognya berubah.
 *
 * Website menyimpan halaman hasil render di cache-nya sendiri, jadi tanpa ping
 * ini perubahan produk/category baru muncul setelah cache halaman kedaluwarsa.
 *
 * Pemanggilannya ditunda sampai response ERP selesai dikirim (terminating),
 * supaya admin tidak ikut menunggu website menjawab. Kalau website mati atau
 * secret salah, kegagalan hanya dicatat di log: simpan produk tetap berhasil.
 */
class WebsiteRevalidator
{
    /**
     * @var array<int, string>
     */
    private array $queuedPaths = [];

    private bool $flushRegistered = false;

    public function product(?string $slug = null): void
    {
        $this->queue(array_filter([
            '/',
            '/products',
            $slug ? '/products/' . $slug : null,
        ]));
    }

    public function categories(): void
    {
        $this->queue(['/', '/products']);
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function queue(array $paths): void
    {
        $this->queuedPaths = array_values(array_unique(array_merge($this->queuedPaths, $paths)));

        if ($this->flushRegistered) {
            return;
        }

        $this->flushRegistered = true;

        app()->terminating(fn () => $this->flush());
    }

    private function flush(): void
    {
        $paths = $this->queuedPaths;
        $this->queuedPaths = [];
        $this->flushRegistered = false;

        if ($paths === []) {
            return;
        }

        $baseUrl = rtrim((string) config('services.website.url'), '/');
        $secret = (string) config('services.website.secret');

        // Belum dikonfigurasi: diamkan saja, bukan error yang perlu dicatat tiap simpan.
        if ($baseUrl === '' || $secret === '') {
            return;
        }

        try {
            $response = Http::timeout((int) config('services.website.timeout', 5))
                ->withHeaders(['X-Revalidate-Secret' => $secret])
                ->post($baseUrl . '/api/revalidate', ['paths' => $paths]);

            if ($response->failed()) {
                Log::warning('Revalidate website ditolak', [
                    'status' => $response->status(),
                    'paths' => $paths,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Revalidate website gagal: ' . $e->getMessage(), ['paths' => $paths]);
        }
    }
}
