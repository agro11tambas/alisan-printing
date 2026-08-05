<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Partial action-button di Sale List memanggil $order->is_fully_returned dan
 * $order->has_delivery_list untuk setiap baris. Versi lama accessor itu selalu
 * menembak query baru, jadi satu halaman berisi 15 order membayar 45 query
 * tambahan hanya untuk menentukan dua tombol muncul atau tidak.
 *
 * Sekarang keduanya memakai relasi yang sudah di-eager-load. Test ini menjaga
 * agar tidak diam-diam kembali menembak query per baris, sekaligus memastikan
 * hasilnya tetap sama seperti versi query.
 */
class OrderAccessorQueryCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedOrders();
    }

    protected function tearDown(): void
    {
        foreach (['delivery_lists', 'delivery_orders', 'sale_return_items', 'sale_returns', 'order_items', 'orders'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_accessors_run_no_queries_when_relations_are_eager_loaded(): void
    {
        $orders = $this->eagerLoadedOrders();

        $queries = $this->countQueries(function () use ($orders) {
            foreach ($orders as $order) {
                $order->is_fully_returned;
                $order->has_delivery_list;
            }
        });

        $this->assertSame(0, $queries, "Accessor masih menembak {$queries} query padahal relasinya sudah dimuat.");
    }

    public function test_results_match_the_query_based_fallback(): void
    {
        $eager = $this->eagerLoadedOrders()->keyBy('id');
        $lazy = Order::orderBy('id')->get()->keyBy('id');

        foreach ($eager as $id => $order) {
            $this->assertSame(
                $lazy[$id]->is_fully_returned,
                $order->is_fully_returned,
                "is_fully_returned berbeda untuk order {$id}."
            );

            $this->assertSame(
                $lazy[$id]->has_delivery_list,
                $order->has_delivery_list,
                "has_delivery_list berbeda untuk order {$id}."
            );
        }
    }

    public function test_it_reads_the_expected_values(): void
    {
        $orders = $this->eagerLoadedOrders()->keyBy('id');

        // Order 1: 10 dipesan, 10 diretur, punya pengiriman Finished.
        $this->assertTrue($orders[1]->is_fully_returned);
        $this->assertTrue($orders[1]->has_delivery_list);

        // Order 2: 10 dipesan, baru 4 diretur, pengiriman masih Ongoing.
        $this->assertFalse($orders[2]->is_fully_returned);
        $this->assertFalse($orders[2]->has_delivery_list);

        // Order 3: tidak punya item sama sekali.
        $this->assertFalse($orders[3]->is_fully_returned);
        $this->assertFalse($orders[3]->has_delivery_list);
    }

    private function eagerLoadedOrders()
    {
        return Order::with([
            'orderItems',
            'saleReturns.items',
            'deliveryOrders.shipments',
        ])->orderBy('id')->get();
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;

        DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        return $count;
    }

    private function createSchema(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_order_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_return_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->string('status')->default('Ongoing');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedOrders(): void
    {
        DB::table('orders')->insert([
            ['id' => 1], ['id' => 2], ['id' => 3],
        ]);

        DB::table('order_items')->insert([
            ['order_id' => 1, 'quantity' => 10],
            ['order_id' => 2, 'quantity' => 10],
        ]);

        DB::table('sale_returns')->insert([
            ['id' => 1, 'sale_order_id' => 1],
            ['id' => 2, 'sale_order_id' => 2],
        ]);

        DB::table('sale_return_items')->insert([
            ['sale_return_id' => 1, 'quantity' => 10],
            ['sale_return_id' => 2, 'quantity' => 4],
        ]);

        DB::table('delivery_orders')->insert([
            ['id' => 1, 'order_id' => 1],
            ['id' => 2, 'order_id' => 2],
        ]);

        DB::table('delivery_lists')->insert([
            ['delivery_order_id' => 1, 'status' => 'Finished'],
            ['delivery_order_id' => 2, 'status' => 'Ongoing'],
        ]);
    }
}
