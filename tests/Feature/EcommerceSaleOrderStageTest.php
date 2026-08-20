<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tab pesanan di website: Menunggu Verifikasi / Diproses / Selesai.
 *
 * Tahapnya dihitung dari status order plus assign waiting list dan delivery,
 * jadi test ini menyiapkan rantai order -> order progress -> assign dan
 * order -> delivery order -> delivery list.
 */
class EcommerceSaleOrderStageTest extends TestCase
{
    private const TABLES = [
        'delivery_lists',
        'delivery_order_items',
        'delivery_orders',
        'order_progress_assigns',
        'order_progress_items',
        'order_progresses_2',
        'order_items',
        'orders',
        'products',
        'customer_addresses',
        'customer_account_customer',
        'customer_accounts',
        'customers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('whatsapp_number')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_account_customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_id');
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('business_name')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('customer_account_id')->nullable();
            $table->unsignedBigInteger('customer_address_id')->nullable();
            $table->string('order_number')->unique();
            $table->dateTime('order_date');
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('business_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('google_maps')->nullable();
            $table->string('mode')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_bundle_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('mode')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('completed_quantity')->default(0);
            $table->integer('price')->default(0);
            $table->integer('subtotal')->default(0);
            $table->integer('total_after_discount')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_progresses_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('design_id')->nullable();
            $table->string('status')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_progress_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_progress_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('completed_quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_progress_assigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_progress_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('assigned_quantity')->default(0);
            $table->integer('completed_quantity')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('design_id')->nullable();
            $table->string('delivery_number')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('order_progress_id')->nullable();
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('order_progress_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('status')->nullable();
            $table->integer('progress_qty')->default(0);
            $table->integer('ready_qty')->default(0);
            $table->integer('shipped_qty')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->string('shipment_number')->nullable();
            $table->date('shipment_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_unverified_order_is_waiting_for_verification(): void
    {
        $account = $this->account();
        $this->order($account, 'SO/1/ALS/050826', 'Sale Order');

        $fulfillment = $this->fetchFulfillment($account, 'SO/1/ALS/050826');

        $this->assertSame('waiting_verification', $fulfillment['stage']);
        $this->assertFalse($fulfillment['is_verified']);
    }

    public function test_verified_order_with_assign_list_is_processing(): void
    {
        $account = $this->account();
        $orderId = $this->order($account, 'INV/1/ALS/050826', 'Sale List');
        $this->assignWaitingList($orderId);

        $fulfillment = $this->fetchFulfillment($account, 'INV/1/ALS/050826');

        $this->assertSame('processing', $fulfillment['stage']);
        $this->assertTrue($fulfillment['has_production_assign']);
        $this->assertFalse($fulfillment['is_on_delivery']);
    }

    public function test_order_being_shipped_is_still_processing(): void
    {
        $account = $this->account();
        $orderId = $this->order($account, 'INV/2/ALS/050826', 'Sale List');
        $this->delivery($orderId, shippedQty: 500, shipmentStatus: 'Ongoing');

        $fulfillment = $this->fetchFulfillment($account, 'INV/2/ALS/050826');

        $this->assertSame('processing', $fulfillment['stage']);
        $this->assertTrue($fulfillment['is_on_delivery']);
        $this->assertFalse($fulfillment['is_fully_delivered']);
    }

    public function test_partially_shipped_order_is_not_completed(): void
    {
        $account = $this->account();
        $orderId = $this->order($account, 'INV/3/ALS/050826', 'Sale List');
        $this->delivery($orderId, shippedQty: 200, shipmentStatus: 'Finished');

        $fulfillment = $this->fetchFulfillment($account, 'INV/3/ALS/050826');

        $this->assertSame('processing', $fulfillment['stage']);
        $this->assertFalse($fulfillment['is_fully_delivered']);
    }

    public function test_order_is_completed_once_everything_is_delivered(): void
    {
        $account = $this->account();
        $orderId = $this->order($account, 'INV/4/ALS/050826', 'Sale List');
        $this->delivery($orderId, shippedQty: 500, shipmentStatus: 'Finished');

        $fulfillment = $this->fetchFulfillment($account, 'INV/4/ALS/050826');

        $this->assertSame('completed', $fulfillment['stage']);
        $this->assertTrue($fulfillment['is_fully_delivered']);
        $this->assertFalse($fulfillment['is_on_delivery']);
    }

    public function test_order_with_an_item_outside_delivery_order_is_not_completed(): void
    {
        $account = $this->account();
        $orderId = $this->order($account, 'INV/5/ALS/050826', 'Sale List');
        $this->delivery($orderId, shippedQty: 500, shipmentStatus: 'Finished');

        // Item kedua belum pernah masuk delivery order — masih diproduksi.
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => 1,
            'product_name' => 'Product 3',
            'quantity' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fulfillment = $this->fetchFulfillment($account, 'INV/5/ALS/050826');

        $this->assertSame('processing', $fulfillment['stage']);
    }

    private function account(): CustomerAccount
    {
        return CustomerAccount::create([
            'name' => 'Website Test',
            'whatsapp_number' => '081234567890',
            'password' => 'hashed-password',
            'is_active' => true,
        ]);
    }

    private function order(CustomerAccount $account, string $orderNumber, string $status): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Outlet Website Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insertOrIgnore([
            'id' => 1,
            'name' => 'Product 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'customer_id' => $customerId,
            'customer_account_id' => $account->id,
            'order_number' => $orderNumber,
            'order_date' => now(),
            'status' => $status,
            'payment_status' => 'Unpaid',
            'grand_total' => 360000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => 1,
            'product_name' => 'Product 1 + Product 2 (BUNDLE)',
            'quantity' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $orderId;
    }

    private function assignWaitingList(int $orderId): void
    {
        $progressId = DB::table('order_progresses_2')->insertGetId([
            'order_id' => $orderId,
            'status' => 'On Progress',
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $progressItemId = DB::table('order_progress_items')->insertGetId([
            'order_progress_id' => $progressId,
            'order_item_id' => DB::table('order_items')->where('order_id', $orderId)->value('id'),
            'product_id' => 1,
            'quantity' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_progress_assigns')->insert([
            'order_progress_item_id' => $progressItemId,
            'product_id' => 1,
            'assigned_quantity' => 500,
            'status' => 'On Progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function delivery(int $orderId, int $shippedQty, string $shipmentStatus): void
    {
        $deliveryOrderId = DB::table('delivery_orders')->insertGetId([
            'order_id' => $orderId,
            'delivery_number' => 'DO/' . $orderId,
            'delivery_date' => now()->toDateString(),
            'status' => 'Ongoing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('delivery_order_items')->insert([
            'delivery_order_id' => $deliveryOrderId,
            'order_item_id' => DB::table('order_items')->where('order_id', $orderId)->value('id'),
            'product_id' => 1,
            'status' => 'Pending',
            'progress_qty' => 500,
            'ready_qty' => 500,
            'shipped_qty' => $shippedQty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('delivery_lists')->insert([
            'delivery_order_id' => $deliveryOrderId,
            'shipment_number' => 'DL/' . $orderId,
            'shipment_date' => now()->toDateString(),
            'status' => $shipmentStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function fetchFulfillment(CustomerAccount $account, string $orderNumber): array
    {
        Sanctum::actingAs($account);

        $response = $this->getJson('/api/v1/ecommerce/sale-orders')->assertOk();

        $order = collect($response->json('data.data'))
            ->firstWhere('order_number', $orderNumber);

        $this->assertNotNull($order, "Order {$orderNumber} tidak muncul di daftar pesanan customer.");

        return $order['fulfillment'];
    }
}
