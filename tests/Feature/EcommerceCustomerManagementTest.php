<?php

namespace Tests\Feature;

use Tests\TestCase;

class EcommerceCustomerManagementTest extends TestCase
{
    public function test_customers_cannot_add_businesses_from_the_ecommerce_api(): void
    {
        $this->postJson('/api/v1/ecommerce/auth/businesses', [
            'name' => 'Bisnis Baru',
        ])->assertNotFound();
    }

    public function test_customers_cannot_add_addresses_from_the_ecommerce_api(): void
    {
        $this->postJson('/api/v1/ecommerce/auth/businesses/1/addresses', [
            'address' => 'Alamat baru',
        ])->assertNotFound();
    }
}
