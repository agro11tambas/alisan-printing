<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TempDeleteSaleOrderTest extends TestCase
{
    public function test_delete_sale_order_ajax(): void
    {
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'alisan', 'database.connections.mysql.host' => '127.0.0.1', 'database.connections.mysql.username' => 'root', 'database.connections.mysql.password' => '']);
        DB::purge('mysql');

        $user = User::query()->first();
        $this->assertNotNull($user, 'no user');

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order, 'no order');

        fwrite(STDERR, "USER: {$user->id} / order: {$order->id} ({$order->order_number})\n");

        DB::beginTransaction();

        try {
            $response = $this->actingAs($user)
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->delete('/erp/sales/sale-orders/delete/' . $order->id);

            fwrite(STDERR, "STATUS: " . $response->getStatusCode() . "\n");
            fwrite(STDERR, "CTYPE : " . $response->headers->get('content-type') . "\n");
            fwrite(STDERR, "BODY  : " . substr($response->getContent(), 0, 600) . "\n");
        } finally {
            DB::rollBack();
        }

        $this->assertTrue(true);
    }
}
