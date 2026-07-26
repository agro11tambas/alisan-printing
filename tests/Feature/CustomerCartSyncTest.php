<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCartSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp_number')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();
            $table->string('cart_item_key', 191);
            $table->unsignedInteger('quantity');
            $table->boolean('is_selected')->default(true);
            $table->json('item_data');
            $table->timestamps();
            $table->unique(['customer_account_id', 'cart_item_key']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_cart_items');
        Schema::dropIfExists('customer_accounts');

        parent::tearDown();
    }

    public function test_cart_routes_require_customer_authentication(): void
    {
        $this->getJson('/api/v1/ecommerce/cart')->assertUnauthorized();
        $this->putJson('/api/v1/ecommerce/cart', ['items' => []])->assertUnauthorized();
    }

    public function test_customer_cart_is_synced_and_isolated_per_account(): void
    {
        $firstAccount = CustomerAccount::create([
            'name' => 'Customer One',
            'whatsapp_number' => '081111111111',
            'password' => 'hashed-password',
            'is_active' => true,
        ]);
        $secondAccount = CustomerAccount::create([
            'name' => 'Customer Two',
            'whatsapp_number' => '082222222222',
            'password' => 'hashed-password',
            'is_active' => true,
        ]);

        Sanctum::actingAs($firstAccount);

        $this->putJson('/api/v1/ecommerce/cart', [
            'items' => [
                [
                    'cart_item_key' => 'single-10',
                    'quantity' => 3,
                    'is_selected' => true,
                    'item_data' => [
                        'id' => 'single-10',
                        'type' => 'single',
                        'productGroupId' => 1,
                        'price' => 1200,
                    ],
                ],
                [
                    'cart_item_key' => 'bundle-20-21',
                    'quantity' => 2,
                    'is_selected' => false,
                    'item_data' => [
                        'id' => 'bundle-20-21',
                        'type' => 'bundle',
                        'productGroupId' => 2,
                        'price' => 2500,
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.account_id', $firstAccount->id)
            ->assertJsonCount(2, 'data.items');

        $this->putJson('/api/v1/ecommerce/cart', [
            'items' => [
                [
                    'cart_item_key' => 'single-10',
                    'quantity' => 5,
                    'is_selected' => false,
                    'item_data' => [
                        'id' => 'single-10',
                        'type' => 'single',
                        'productGroupId' => 1,
                        'price' => 1200,
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', 'single-10')
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.items.0.isSelected', false);

        $this->assertDatabaseHas('customer_cart_items', [
            'customer_account_id' => $firstAccount->id,
            'cart_item_key' => 'single-10',
            'quantity' => 5,
            'is_selected' => false,
        ]);
        $this->assertDatabaseMissing('customer_cart_items', [
            'customer_account_id' => $firstAccount->id,
            'cart_item_key' => 'bundle-20-21',
        ]);

        Sanctum::actingAs($secondAccount);

        $this->getJson('/api/v1/ecommerce/cart')
            ->assertOk()
            ->assertJsonPath('data.account_id', $secondAccount->id)
            ->assertJsonCount(0, 'data.items');
    }
}
