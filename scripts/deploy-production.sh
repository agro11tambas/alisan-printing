#!/usr/bin/env bash
#
# Urutan deploy produksi. Jalankan dari direktori aplikasi di server.
#
#   bash scripts/deploy-production.sh
#
# --no-dev penting: tanpa itu Laravel Debugbar ikut ter-install, dan kalau
# APP_DEBUG kebetulan true, Debugbar akan memprofil setiap query lalu menulis
# file JSON ratusan KB per request ke storage/debugbar.

set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Install dependency produksi"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Bersihkan cache bootstrap tanpa mengosongkan cache data aplikasi"
# cache:clear sengaja dilewati. Menghapus file cache katalog membuat request
# pertama sesudah deploy membangun seluruh katalog dan pernah memenuhi worker.
# Versioned invalidation tetap dijalankan aplikasi saat produk/kategori berubah.
php artisan optimize:clear --except=cache

echo "==> Jalankan migrasi"
php artisan migrate --force

echo "==> Cache config, route, event, dan view"
php artisan optimize

echo "==> Restart queue worker"
php artisan queue:restart

echo "==> Cek setelan produksi"
php artisan app:check-production
