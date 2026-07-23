<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Services\PurchaseListForceDeleteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchaseListForceDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_purchase_id')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('status')->nullable();
            $table->string('stock_destination')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('source_purchase_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('inventory_warehouse_id')->nullable();
            $table->unsignedBigInteger('production_warehouse_id')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('qty_base', 15, 3)->nullable();
            $table->decimal('unit_conversion_value', 15, 3)->default(1);
            $table->decimal('stock_in', 15, 3)->default(0);
            $table->string('status')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('freight', 15, 2)->default(0);
            $table->decimal('final_price', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventories_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_items_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('inventory_warehouse_id')->nullable();
            $table->unsignedBigInteger('production_warehouse_id')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('qty_base', 15, 3)->default(0);
            $table->decimal('stock_in', 15, 3)->default(0);
            $table->decimal('remaining_stock_in', 15, 3)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_stock_ins_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->string('waybill_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_stock_in_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('stock_in', 15, 3)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('inventory_warehouse_id');
            $table->decimal('inventory_stock', 15, 3)->default(0);
            $table->decimal('available_quantity', 15, 3)->default(0);
            $table->decimal('stock_after_sales', 15, 3)->default(0);
            $table->decimal('incoming_stock', 15, 3)->default(0);
            $table->timestamps();
        });
        Schema::create('production_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('production_warehouse_id');
            $table->decimal('available_quantity', 15, 3)->default(0);
            $table->decimal('incoming_stock', 15, 3)->default(0);
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('proof')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('purchase_edit_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference_table');
            $table->unsignedBigInteger('reference_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('defect_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'defect_products',
            'financial_reports',
            'purchase_edit_histories',
            'purchase_returns',
            'account_transactions',
            'accounts',
            'production_stocks',
            'inventory_stocks',
            'inventory_stock_in_histories_2',
            'inventory_stock_ins_2',
            'inventory_items_2',
            'inventories_2',
            'purchase_items',
            'purchases',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_force_delete_warehouse_child_reverses_stock_payment_and_related_records(): void
    {
        [$parentId, $childId, $itemId, $inventoryId, $inventoryItemId] =
            $this->createChildPurchase('warehouse', 4);

        DB::table('inventory_stocks')->insert([
            'product_id' => 100,
            'inventory_warehouse_id' => 1,
            'inventory_stock' => 20,
            'available_quantity' => 18,
            'stock_after_sales' => 15,
            'incoming_stock' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $purchaseAccountId = DB::table('accounts')->insertGetId([
            'name' => 'Purchase',
            'type' => 'Purchase Account',
            'closing_balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cashAccountId = DB::table('accounts')->insertGetId([
            'name' => 'Cash',
            'type' => 'Cash',
            'closing_balance' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_transactions')->insert([
            [
                'purchase_id' => $childId,
                'account_id' => $purchaseAccountId,
                'debit' => 100,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'purchase_id' => $childId,
                'account_id' => $cashAccountId,
                'debit' => 0,
                'credit' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $stockInId = DB::table('inventory_stock_ins_2')->insertGetId([
            'inventory_id' => $inventoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_stock_in_histories_2')->insert([
            'inventory_stock_in_id' => $stockInId,
            'inventory_item_id' => $inventoryItemId,
            'stock_in' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('financial_reports')->insert([
            'reference_table' => 'purchases',
            'reference_id' => $childId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PurchaseListForceDeleteService::class)->execute(Purchase::findOrFail($childId));

        $stock = DB::table('inventory_stocks')->first();
        $this->assertEquals(16, $stock->inventory_stock);
        $this->assertEquals(14, $stock->available_quantity);
        $this->assertEquals(11, $stock->stock_after_sales);
        $this->assertEquals(0, $stock->incoming_stock);
        $this->assertEquals(900, DB::table('accounts')->where('id', $purchaseAccountId)->value('closing_balance'));
        $this->assertEquals(540, DB::table('accounts')->where('id', $cashAccountId)->value('closing_balance'));
        $this->assertDatabaseHas('purchases', ['id' => $parentId]);
        $this->assertDatabaseMissing('purchases', ['id' => $childId]);

        foreach ([
            'account_transactions',
            'inventory_stock_in_histories_2',
            'inventory_stock_ins_2',
            'inventory_items_2',
            'inventories_2',
            'financial_reports',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseMissing('purchase_items', ['id' => $itemId]);
    }

    public function test_force_delete_production_child_only_reduces_received_available_stock(): void
    {
        [, $childId] = $this->createChildPurchase('production', 4);
        DB::table('production_stocks')->insert([
            'product_id' => 100,
            'production_warehouse_id' => 2,
            'available_quantity' => 20,
            'incoming_stock' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PurchaseListForceDeleteService::class)->execute(Purchase::findOrFail($childId));

        $stock = DB::table('production_stocks')->first();
        $this->assertEquals(16, $stock->available_quantity);
        $this->assertEquals(0, $stock->incoming_stock);
    }

    public function test_force_delete_without_stock_in_keeps_available_stock_unchanged(): void
    {
        [, $childId] = $this->createChildPurchase('production', 0);
        DB::table('production_stocks')->insert([
            'product_id' => 100,
            'production_warehouse_id' => 2,
            'available_quantity' => 20,
            'incoming_stock' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PurchaseListForceDeleteService::class)->execute(Purchase::findOrFail($childId));

        $stock = DB::table('production_stocks')->first();
        $this->assertEquals(20, $stock->available_quantity);
        $this->assertEquals(0, $stock->incoming_stock);
    }

    public function test_force_delete_is_blocked_when_child_has_a_purchase_return(): void
    {
        [, $childId] = $this->createChildPurchase('warehouse', 0);
        DB::table('purchase_returns')->insert([
            'purchase_id' => $childId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah memiliki Purchase Return');

        app(PurchaseListForceDeleteService::class)->execute(Purchase::findOrFail($childId));
    }

    private function createChildPurchase(string $destination, float $stockIn): array
    {
        $parentId = DB::table('purchases')->insertGetId([
            'purchase_number' => 'PO/TEST',
            'status' => 'Purchase Orders',
            'stock_destination' => $destination,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parentItemId = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $parentId,
            'product_id' => 100,
            'quantity' => 10,
            'qty_base' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $childId = DB::table('purchases')->insertGetId([
            'parent_purchase_id' => $parentId,
            'purchase_number' => 'PL/TEST',
            'status' => 'Purchase List',
            'stock_destination' => $destination,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $childId,
            'source_purchase_item_id' => $parentItemId,
            'product_id' => 100,
            'inventory_warehouse_id' => $destination === 'warehouse' ? 1 : null,
            'production_warehouse_id' => $destination === 'production' ? 2 : null,
            'quantity' => 10,
            'qty_base' => 10,
            'unit_conversion_value' => 1,
            'stock_in' => $stockIn,
            'status' => 'Purchase Account',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inventoryId = DB::table('inventories_2')->insertGetId([
            'purchase_id' => $childId,
            'purchase_number' => 'PL/TEST',
            'status' => 'Stock In',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inventoryItemId = DB::table('inventory_items_2')->insertGetId([
            'inventory_id' => $inventoryId,
            'purchase_item_id' => $itemId,
            'product_id' => 100,
            'inventory_warehouse_id' => $destination === 'warehouse' ? 1 : null,
            'production_warehouse_id' => $destination === 'production' ? 2 : null,
            'quantity' => 10,
            'qty_base' => 10,
            'stock_in' => $stockIn,
            'remaining_stock_in' => 10 - $stockIn,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$parentId, $childId, $itemId, $inventoryId, $inventoryItemId];
    }
}
