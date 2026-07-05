<?php

namespace Database\Seeders;

use App\Models\CustomerAccount;
use App\Models\CustomerAddresses;
use App\Models\Customers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $phone = '6281234567890';

            $customer = Customers::withTrashed()
                ->where('phone', $phone)
                ->first();

            if (!$customer) {
                $customer = Customers::create([
                    'name' => 'Customer Website Test',
                    'phone' => $phone,
                    'customer_deposit' => 0,
                ]);
            } else {
                if ($customer->trashed()) {
                    $customer->restore();
                }

                $customer->update([
                    'name' => 'Customer Website Test',
                    'phone' => $phone,
                    'customer_deposit' => $customer->customer_deposit ?? 0,
                ]);
            }

            $account = CustomerAccount::withTrashed()
                ->where('whatsapp_number', $phone)
                ->first();

            if (!$account) {
                $account = CustomerAccount::create([
                    'customer_id' => $customer->id,
                    'name' => 'Customer Website Test',
                    'email' => 'customer@test.com',
                    'whatsapp_number' => $phone,
                    'password' => Hash::make('customer123'),
                    'auth_provider' => 'manual',
                    'is_active' => true,
                ]);
            } else {
                if ($account->trashed()) {
                    $account->restore();
                }

                $account->update([
                    'customer_id' => $customer->id,
                    'name' => 'Customer Website Test',
                    'email' => 'customer@test.com',
                    'whatsapp_number' => $phone,
                    'password' => Hash::make('customer123'),
                    'auth_provider' => 'manual',
                    'is_active' => true,
                ]);
            }

            $customer->accounts()->syncWithoutDetaching([
                $account->id => [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $address = CustomerAddresses::withTrashed()
                ->where('customer_id', $customer->id)
                ->where('address', 'Jl. Website Test No. 1, Jakarta')
                ->first();

            if (!$address) {
                $customer->addresses()->create([
                    'business_name' => 'Outlet Website Test',
                    'address' => 'Jl. Website Test No. 1, Jakarta',
                    'google_maps' => 'https://maps.google.com/?q=Jakarta',
                ]);

                return;
            }

            if ($address->trashed()) {
                $address->restore();
            }

            $address->update([
                'business_name' => 'Outlet Website Test',
                'address' => 'Jl. Website Test No. 1, Jakarta',
                'google_maps' => 'https://maps.google.com/?q=Jakarta',
            ]);
        });
    }
}
