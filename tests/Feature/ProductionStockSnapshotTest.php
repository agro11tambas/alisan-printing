<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\OrderProgressAssign;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use App\Models\StockOpnameProduction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionStockSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('production_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('available_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('production_stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('opening_stock')->default(0);
            $table->integer('closing_stock')->default(0);
            $table->integer('stock_in_today')->default(0);
            $table->integer('assign_today')->default(0);
            $table->integer('stock_opname_today')->default(0);
            $table->date('snapshot_date');
            $table->timestamps();
            $table->unique(['product_id', 'snapshot_date']);
        });

        Schema::create('order_progress_assigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assign_batch_id')->nullable();
            $table->unsignedBigInteger('order_progress_item_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->integer('assigned_quantity')->default(0);
            $table->integer('completed_quantity')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_progress_histories_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_progress_assign_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventories_2', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('material_request_item_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_stock_ins_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('change_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_stock_in_histories_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_stock_in_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('stock_in')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('production_warehouse_id');
            $table->date('date');
            $table->string('change')->nullable();
            $table->integer('finished_product')->nullable();
            $table->integer('available_quantity')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('stock_opname_productions');
        Schema::dropIfExists('inventory_stock_in_histories_2');
        Schema::dropIfExists('inventory_stock_ins_2');
        Schema::dropIfExists('inventory_items_2');
        Schema::dropIfExists('inventories_2');
        Schema::dropIfExists('order_progress_histories_2');
        Schema::dropIfExists('order_progress_assigns');
        Schema::dropIfExists('production_stock_snapshots');
        Schema::dropIfExists('production_stocks');

        parent::tearDown();
    }

    public function test_stock_opname_changes_are_synchronized_to_the_snapshot(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        $stockOpname = StockOpnameProduction::create([
            'product_id' => 1,
            'production_warehouse_id' => 1,
            'date' => '2026-07-21',
            'change' => 'available_quantity',
            'available_quantity' => 10,
            'status' => 'Gain',
        ]);

        $snapshot = ProductionStockSnapshot::firstOrFail();
        $this->assertSame(100, $snapshot->opening_stock);
        $this->assertSame(10, $snapshot->stock_opname_today);
        // stock opname ikut rumus closing: 100 - 0 + 0 + 10
        $this->assertSame(110, $snapshot->closing_stock);

        $stockOpname->update([
            'available_quantity' => 4,
            'status' => 'Loss',
        ]);

        $this->assertSame(-4, $snapshot->fresh()->stock_opname_today);
        $this->assertSame(96, $snapshot->fresh()->closing_stock); // 100 - 0 + 0 - 4

        $stockOpname->delete();

        $this->assertSame(0, $snapshot->fresh()->stock_opname_today);
        $this->assertSame(100, $snapshot->fresh()->closing_stock);
    }

    public function test_stock_opname_is_carried_forward_to_snapshots_that_already_exist(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 20000,
        ]);

        // Kondisi seperti di produksi: snapshot tanggal 8 dan tanggal 10 sudah terbentuk,
        // tanggal 9 bolong karena command harian tidak jalan.
        ProductionStockSnapshot::create([
            'product_id' => 1,
            'snapshot_date' => '2026-08-08',
            'opening_stock' => 33000,
            'closing_stock' => 33000,
        ]);

        ProductionStockSnapshot::create([
            'product_id' => 1,
            'snapshot_date' => '2026-08-10',
            'opening_stock' => 33000,
            'closing_stock' => 25000,
            'assign_today' => 8000,
        ]);

        // Opname Loss 5000 di tanggal 8
        StockOpnameProduction::create([
            'product_id' => 1,
            'production_warehouse_id' => 1,
            'date' => '2026-08-08',
            'change' => 'available_quantity',
            'available_quantity' => 5000,
            'status' => 'Loss',
        ]);

        $eight = ProductionStockSnapshot::whereDate('snapshot_date', '2026-08-08')->firstOrFail();
        $this->assertSame(-5000, $eight->stock_opname_today);
        $this->assertSame(28000, $eight->closing_stock); // 33000 - 0 + 0 - 5000

        // Inti bug-nya: opname tanggal 8 harus ikut menggeser tanggal 10, bukan berhenti di tanggal 8.
        $ten = ProductionStockSnapshot::whereDate('snapshot_date', '2026-08-10')->firstOrFail();
        $this->assertSame(28000, $ten->opening_stock);
        $this->assertSame(20000, $ten->closing_stock); // 28000 - 8000
    }

    public function test_assign_changes_are_synchronized_to_the_snapshot(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        $assign = OrderProgressAssign::create([
            'product_id' => 1,
            'assigned_quantity' => 15,
        ]);

        $snapshot = ProductionStockSnapshot::firstOrFail();
        $this->assertSame(today()->toDateString(), $snapshot->snapshot_date->toDateString());
        $this->assertSame(100, $snapshot->opening_stock);
        $this->assertSame(15, $snapshot->assign_today);

        // assign kedua menambah, bukan menimpa
        OrderProgressAssign::create([
            'product_id' => 1,
            'assigned_quantity' => 5,
        ]);

        $this->assertSame(20, $snapshot->fresh()->assign_today);
        $this->assertSame(80, $snapshot->fresh()->closing_stock);

        $assign->update(['assigned_quantity' => 8]);

        $this->assertSame(13, $snapshot->fresh()->assign_today);
        $this->assertSame(87, $snapshot->fresh()->closing_stock);

        $assign->delete();

        $this->assertSame(5, $snapshot->fresh()->assign_today);

        $assign->restore();

        $this->assertSame(13, $snapshot->fresh()->assign_today);

        // force delete setelah soft delete tidak boleh mengurangi dua kali
        $assign->delete();
        $assign->forceDelete();

        $this->assertSame(5, $snapshot->fresh()->assign_today);
    }

    public function test_stock_in_production_is_recorded_to_the_snapshot_immediately(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        $inventory = Inventory::create(['status' => 'Stock In Production']);
        $inventoryItem = InventoryItem::create([
            'inventory_id' => $inventory->id,
            'product_id' => 1,
            'purchase_item_id' => 99,
        ]);
        $stockIn = InventoryStockIn::create([
            'inventory_id' => $inventory->id,
            'change_date' => today()->toDateString(),
        ]);

        $history = InventoryStockInHistory::create([
            'inventory_stock_in_id' => $stockIn->id,
            'inventory_item_id' => $inventoryItem->id,
            'stock_in' => 250,
        ]);

        // Inti bug-nya: dulu baris snapshot ini baru terisi waktu command
        // stock:snapshot jalan (00:00 / 23:59), bukan saat stock in-nya dilakukan.
        $snapshot = ProductionStockSnapshot::whereDate('snapshot_date', today())->firstOrFail();
        $this->assertSame(100, $snapshot->opening_stock);
        $this->assertSame(250, $snapshot->stock_in_today);
        $this->assertSame(350, $snapshot->closing_stock); // 100 - 0 + 250

        // Kolom tersimpan dan hitung ulang dari tabel sumber harus sama angkanya
        $this->assertSame(250, ProductionStockSnapshot::stockInTodayFor(1, today()));

        // Koreksi qty ikut jalan
        $history->update(['stock_in' => 200]);

        $this->assertSame(200, $snapshot->fresh()->stock_in_today);
        $this->assertSame(300, $snapshot->fresh()->closing_stock);

        // Dibatalkan
        $history->delete();

        $this->assertSame(0, $snapshot->fresh()->stock_in_today);
        $this->assertSame(100, $snapshot->fresh()->closing_stock);

        $history->restore();

        $this->assertSame(200, $snapshot->fresh()->stock_in_today);

        // force delete setelah soft delete tidak boleh mengurangi dua kali
        $history->delete();
        $history->forceDelete();

        $this->assertSame(0, $snapshot->fresh()->stock_in_today);
    }

    public function test_stock_in_to_the_warehouse_does_not_touch_the_production_snapshot(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        // Status 'Stock In' = masuk gudang, bukan produksi.
        $inventory = Inventory::create(['status' => 'Stock In']);
        $inventoryItem = InventoryItem::create([
            'inventory_id' => $inventory->id,
            'product_id' => 1,
            'purchase_item_id' => 99,
        ]);
        $stockIn = InventoryStockIn::create([
            'inventory_id' => $inventory->id,
            'change_date' => today()->toDateString(),
        ]);

        InventoryStockInHistory::create([
            'inventory_stock_in_id' => $stockIn->id,
            'inventory_item_id' => $inventoryItem->id,
            'stock_in' => 250,
        ]);

        $this->assertNull(ProductionStockSnapshot::whereDate('snapshot_date', today())->first());
    }

    public function test_stock_in_that_came_from_a_material_request_is_not_counted(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        $inventory = Inventory::create(['status' => 'Stock In Production']);
        // Barang dari material request: purchase_item_id kosong, bukan barang beli baru.
        $inventoryItem = InventoryItem::create([
            'inventory_id' => $inventory->id,
            'product_id' => 1,
            'material_request_item_id' => 7,
        ]);
        $stockIn = InventoryStockIn::create([
            'inventory_id' => $inventory->id,
            'change_date' => today()->toDateString(),
        ]);

        InventoryStockInHistory::create([
            'inventory_stock_in_id' => $stockIn->id,
            'inventory_item_id' => $inventoryItem->id,
            'stock_in' => 250,
        ]);

        $this->assertNull(ProductionStockSnapshot::whereDate('snapshot_date', today())->first());
        $this->assertSame(0, ProductionStockSnapshot::stockInTodayFor(1, today()));
    }

    public function test_opening_stock_is_taken_from_the_previous_day_closing_stock(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        // Snapshot kemarin: closing 70
        ProductionStockSnapshot::create([
            'product_id' => 1,
            'snapshot_date' => today()->subDay(),
            'opening_stock' => 90,
            'closing_stock' => 70,
        ]);

        OrderProgressAssign::create([
            'product_id' => 1,
            'assigned_quantity' => 15,
        ]);

        $snapshot = ProductionStockSnapshot::whereDate('snapshot_date', today())->firstOrFail();

        // Opening hari ini = closing kemarin, bukan available_quantity (100)
        $this->assertSame(70, $snapshot->opening_stock);
        $this->assertSame(15, $snapshot->assign_today);
        $this->assertSame(55, $snapshot->closing_stock); // 70 - 15 + 0
    }

    public function test_opening_stock_never_changes_once_the_snapshot_exists(): void
    {
        ProductionStock::create([
            'product_id' => 1,
            'available_quantity' => 100,
        ]);

        ProductionStockSnapshot::create([
            'product_id' => 1,
            'snapshot_date' => today()->subDay(),
            'opening_stock' => 90,
            'closing_stock' => 70,
        ]);

        OrderProgressAssign::create([
            'product_id' => 1,
            'assigned_quantity' => 15,
        ]);

        // Stok real berubah setelah snapshot hari ini terbentuk
        ProductionStock::where('product_id', 1)->update(['available_quantity' => 42]);

        OrderProgressAssign::create([
            'product_id' => 1,
            'assigned_quantity' => 5,
        ]);

        $snapshot = ProductionStockSnapshot::whereDate('snapshot_date', today())->firstOrFail();

        $this->assertSame(70, $snapshot->opening_stock);
        $this->assertSame(50, $snapshot->closing_stock); // 70 - 20 + 0
    }
    public function test_realtime_totals_are_aggregated_in_three_queries_for_all_products(): void
    {
        $inventory = Inventory::create(['status' => 'Stock In Production']);
        $item = InventoryItem::create([
            'inventory_id' => $inventory->id,
            'product_id' => 1,
            'purchase_item_id' => 99,
        ]);
        $stockIn = InventoryStockIn::create([
            'inventory_id' => $inventory->id,
            'change_date' => today()->toDateString(),
        ]);
        InventoryStockInHistory::create([
            'inventory_stock_in_id' => $stockIn->id,
            'inventory_item_id' => $item->id,
            'stock_in' => 25,
        ]);
        OrderProgressAssign::create(['product_id' => 1, 'assigned_quantity' => 7]);
        OrderProgressAssign::create(['product_id' => 2, 'assigned_quantity' => 4]);
        StockOpnameProduction::create([
            'product_id' => 2,
            'production_warehouse_id' => 1,
            'date' => today(),
            'available_quantity' => 3,
            'status' => 'Loss',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $stockIns = ProductionStockSnapshot::stockInTodayByProduct([1, 2], today());
        $assigns = ProductionStockSnapshot::assignTodayByProduct([1, 2], today());
        $opnames = ProductionStockSnapshot::stockOpnameTodayByProduct([1, 2], today());

        $this->assertCount(3, DB::getQueryLog());
        $this->assertSame(25, (int) $stockIns[1]);
        $this->assertSame(7, (int) $assigns[1]);
        $this->assertSame(4, (int) $assigns[2]);
        $this->assertSame(-3, (int) $opnames[2]);
    }
}
