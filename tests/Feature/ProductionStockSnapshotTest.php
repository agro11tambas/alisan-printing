<?php

namespace Tests\Feature;

use App\Models\OrderProgressAssign;
use App\Models\ProductionStock;
use App\Models\ProductionStockSnapshot;
use App\Models\StockOpnameProduction;
use Illuminate\Database\Schema\Blueprint;
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

        $stockOpname->update([
            'available_quantity' => 4,
            'status' => 'Loss',
        ]);

        $this->assertSame(-4, $snapshot->fresh()->stock_opname_today);

        $stockOpname->delete();

        $this->assertSame(0, $snapshot->fresh()->stock_opname_today);
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

        $assign->update(['assigned_quantity' => 8]);

        $this->assertSame(13, $snapshot->fresh()->assign_today);

        $assign->delete();

        $this->assertSame(5, $snapshot->fresh()->assign_today);

        $assign->restore();

        $this->assertSame(13, $snapshot->fresh()->assign_today);

        // force delete setelah soft delete tidak boleh mengurangi dua kali
        $assign->delete();
        $assign->forceDelete();

        $this->assertSame(5, $snapshot->fresh()->assign_today);
    }
}
