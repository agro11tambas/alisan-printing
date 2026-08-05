<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductsController;
use App\Models\Products;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Endpoint daftar ERP dulu memakai clone + count() hanya untuk menentukan
 * has_more, sehingga query terberat dijalankan dua kali per request.
 * Sekarang halaman diambil sekali dengan satu baris tambahan.
 *
 * Test ini menjaga agar isi halaman dan penanda has_more tetap sama persis
 * seperti sebelumnya, termasuk di batas halaman terakhir.
 */
class LazyLoadPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_it_returns_the_page_rows_and_flags_the_next_page(): void
    {
        $this->seedProducts(7);

        [$rows, $hasMore] = $this->page(0, 3);

        $this->assertSame(['P01', 'P02', 'P03'], $rows->pluck('name')->all());
        $this->assertTrue($hasMore);
    }

    public function test_it_respects_the_offset(): void
    {
        $this->seedProducts(7);

        [$rows, $hasMore] = $this->page(3, 3);

        $this->assertSame(['P04', 'P05', 'P06'], $rows->pluck('name')->all());
        $this->assertTrue($hasMore);
    }

    /**
     * Batas rawan: baris tambahan yang diambil harus dibuang, dan halaman
     * yang pas habis tidak boleh dilaporkan masih punya lanjutan.
     */
    public function test_it_never_leaks_the_extra_row_on_the_last_page(): void
    {
        $this->seedProducts(6);

        [$rows, $hasMore] = $this->page(3, 3);

        $this->assertCount(3, $rows);
        $this->assertSame(['P04', 'P05', 'P06'], $rows->pluck('name')->all());
        $this->assertFalse($hasMore);
    }

    public function test_a_partial_last_page_has_no_next_page(): void
    {
        $this->seedProducts(5);

        [$rows, $hasMore] = $this->page(3, 3);

        $this->assertSame(['P04', 'P05'], $rows->pluck('name')->all());
        $this->assertFalse($hasMore);
    }

    public function test_an_empty_result_has_no_next_page(): void
    {
        [$rows, $hasMore] = $this->page(0, 3);

        $this->assertTrue($rows->isEmpty());
        $this->assertFalse($hasMore);
    }

    private function seedProducts(int $count): void
    {
        foreach (range(1, $count) as $index) {
            Products::create([
                'name' => sprintf('P%02d', $index),
                'sku' => sprintf('SKU-%02d', $index),
            ]);
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: bool}
     */
    private function page(int $start, int $length): array
    {
        $controller = new ProductsController();

        $call = function () use ($start, $length) {
            return $this->lazyLoadPage(Products::query()->orderBy('name'), $start, $length);
        };

        return $call->call($controller);
    }
}
