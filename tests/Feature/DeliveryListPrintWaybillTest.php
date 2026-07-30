<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Tests\TestCase;

class DeliveryListPrintWaybillTest extends TestCase
{
    public function test_waybill_renders_the_delivery_item_unit_name(): void
    {
        $order = (object) [
            'business_name' => 'Customer Test',
            'customer' => (object) ['phone' => '08123456789'],
            'notes' => null,
        ];

        $deliveryList = (object) [
            'shipment_number' => 'SJ-TEST-001',
            'shipment_date' => '2026-07-30',
            'note' => null,
            'deliveryOrder' => (object) [
                'order' => $order,
                'shipping_address' => 'Alamat Test',
            ],
            'items' => new Collection([
                (object) [
                    'product' => (object) ['name' => 'Produk Test'],
                    'shipped_quantity' => 2,
                    'note' => null,
                    'unit_name' => 'Box',
                ],
            ]),
        ];

        $html = view(
            'erp.pages.deliveries.delivery-list.print-waybill',
            compact('deliveryList', 'order')
        )->render();

        $this->assertStringContainsString('"unit_name":"Box"', $html);
    }
}
