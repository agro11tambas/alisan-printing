<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('production_stocks', function (Blueprint $table): void {
            $table->id();
            $table->integer('available_quantity')->default(0);
            $table->integer('incoming_stock')->default(0);
        });

        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->id();
            $table->integer('inventory_stock')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('incoming_stock')->default(0);
        });

        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('production_stocks');

        parent::tearDown();
    }

    public function test_it_resets_only_the_requested_stock_columns(): void
    {
        DB::table('production_stocks')->insert([
            'available_quantity' => 25,
            'incoming_stock' => 9,
        ]);

        DB::table('inventory_stocks')->insert([
            'inventory_stock' => 30,
            'available_quantity' => 20,
            'incoming_stock' => 7,
        ]);

        $response = $this->post(route('stock-reset.reset'), [
            'confirmation' => '1',
        ]);

        $response->assertRedirect(route('stock-reset.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('production_stocks', [
            'available_quantity' => 0,
            'incoming_stock' => 9,
        ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'inventory_stock' => 0,
            'available_quantity' => 0,
            'incoming_stock' => 7,
        ]);
    }

    public function test_it_requires_explicit_confirmation(): void
    {
        DB::table('production_stocks')->insert([
            'available_quantity' => 25,
            'incoming_stock' => 9,
        ]);

        $response = $this->post(route('stock-reset.reset'));

        $response->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('production_stocks', [
            'available_quantity' => 25,
        ]);
    }
}
