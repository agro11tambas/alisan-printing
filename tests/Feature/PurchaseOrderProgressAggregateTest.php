<?php

namespace Tests\Feature;

use App\Models\PurchaseItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Listing Purchase Order menghitung qty PL dan realisasi Stock In lewat
 * withSum, bukan dengan memuat pohon purchaseListItems.inventoryItems lalu
 * menjumlahkannya di PHP.
 *
 * Test ini mengunci dua hal: angkanya sama dengan penjumlahan manual, dan
 * baris yang sudah di-soft delete tetap tidak ikut terhitung.
 */
class PurchaseOrderProgressAggregateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('source_purchase_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->decimal('stock_in', 15, 3)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_items_2');
        Schema::dropIfExists('purchase_items');

        parent::tearDown();
    }

    public function test_aggregate_matches_manual_sum_of_the_relation_tree(): void
    {
        $orderItem = PurchaseItem::create(['purchase_id' => 1, 'quantity' => 100]);

        $firstList = PurchaseItem::create([
            'purchase_id' => 2,
            'source_purchase_item_id' => $orderItem->id,
            'quantity' => 30,
        ]);
        $secondList = PurchaseItem::create([
            'purchase_id' => 3,
            'source_purchase_item_id' => $orderItem->id,
            'quantity' => 45,
        ]);

        $firstList->inventoryItems()->create(['stock_in' => 12]);
        $firstList->inventoryItems()->create(['stock_in' => 8]);
        $secondList->inventoryItems()->create(['stock_in' => 5]);

        $aggregated = PurchaseItem::query()
            ->withSum('purchaseListItems as approved_quantity', 'quantity')
            ->withSum('purchaseListInventoryItems as stock_in_base', 'stock_in')
            ->findOrFail($orderItem->id);

        $manual = PurchaseItem::with('purchaseListItems.inventoryItems')->findOrFail($orderItem->id);

        $this->assertSame(75.0, (float) $aggregated->approved_quantity);
        $this->assertSame(25.0, (float) $aggregated->stock_in_base);

        $this->assertSame(
            (float) $manual->purchaseListItems->sum('quantity'),
            (float) $aggregated->approved_quantity
        );
        $this->assertSame(
            (float) $manual->purchaseListItems->sum(fn ($item) => $item->inventoryItems->sum('stock_in')),
            (float) $aggregated->stock_in_base
        );
    }

    public function test_soft_deleted_rows_are_left_out_of_both_sums(): void
    {
        $orderItem = PurchaseItem::create(['purchase_id' => 1, 'quantity' => 100]);

        $keptList = PurchaseItem::create([
            'purchase_id' => 2,
            'source_purchase_item_id' => $orderItem->id,
            'quantity' => 30,
        ]);
        $removedList = PurchaseItem::create([
            'purchase_id' => 3,
            'source_purchase_item_id' => $orderItem->id,
            'quantity' => 45,
        ]);

        $keptList->inventoryItems()->create(['stock_in' => 12]);
        $keptList->inventoryItems()->create(['stock_in' => 8])->delete();
        $removedList->inventoryItems()->create(['stock_in' => 5]);

        // Item PL yang dihapus ikut membatalkan inventory item di bawahnya.
        $removedList->delete();

        $aggregated = PurchaseItem::query()
            ->withSum('purchaseListItems as approved_quantity', 'quantity')
            ->withSum('purchaseListInventoryItems as stock_in_base', 'stock_in')
            ->findOrFail($orderItem->id);

        $this->assertSame(30.0, (float) $aggregated->approved_quantity);
        $this->assertSame(12.0, (float) $aggregated->stock_in_base);
    }
}
