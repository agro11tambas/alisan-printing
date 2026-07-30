<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Services\DeliveryListService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryListNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-30 12:00:00');

        Schema::create('delivery_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id')->nullable();
            $table->string('shipment_number')->unique();
            $table->date('shipment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('delivery_lists');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_generator_never_reuses_numbers_owned_by_soft_deleted_delivery_lists(): void
    {
        DB::table('delivery_lists')->insert([
            [
                'shipment_number' => 'DO/1/ALS/300726',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => now(),
            ],
            [
                'shipment_number' => 'DO/3/ALS/300726',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        $number = DeliveryListService::generateShipmentNumber(new DeliveryOrder());

        $this->assertSame('DO/4/ALS/300726', $number);
    }
}