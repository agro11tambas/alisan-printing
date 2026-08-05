<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Ambil satu halaman untuk infinite scroll tanpa query COUNT terpisah.
     *
     * Endpoint daftar di ERP hanya butuh tahu "masih ada halaman berikutnya
     * atau tidak", tapi pola lama menjalankan clone + count() lebih dulu.
     * Untuk query dengan whereHas (mis. cari produk di Delivery List) itu
     * berarti filter termahal dijalankan dua kali per request.
     *
     * Cukup ambil satu baris lebih banyak dari yang ditampilkan: kalau
     * kelebihan itu ada, berarti masih ada halaman berikutnya.
     *
     * @return array{0: Collection, 1: bool} [baris halaman ini, ada halaman berikutnya]
     */
    protected function lazyLoadPage(Builder $query, int $start, int $length): array
    {
        $rows = $query->skip($start)->take($length + 1)->get();
        $hasMore = $rows->count() > $length;

        return [$rows->take($length)->values(), $hasMore];
    }
}
