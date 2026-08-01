<?php

namespace Tests\Unit;

use App\Models\CustomerAccount;
use App\Models\Customers;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderWhatsappNumberTest extends TestCase
{
    public function test_order_customer_account_number_has_priority(): void
    {
        $order = new Order();
        $order->setRelation('customerAccount', new CustomerAccount([
            'whatsapp_number' => '6281266064331',
        ]));
        $order->setRelation('customer', new Customers([
            'phone' => '6289999999999',
        ]));

        $this->assertSame('6281266064331', $order->order_whatsapp_number);
    }

    public function test_legacy_order_falls_back_to_customer_number(): void
    {
        $order = new Order();
        $order->setRelation('customerAccount', null);
        $order->setRelation('customer', new Customers([
            'phone' => '6287777777777',
        ]));

        $this->assertSame('6287777777777', $order->order_whatsapp_number);
    }
}
