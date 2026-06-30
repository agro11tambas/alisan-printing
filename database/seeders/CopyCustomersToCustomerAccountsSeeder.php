<?php

namespace Database\Seeders;

use App\Models\Customers;
use App\Models\CustomerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CopyCustomersToCustomerAccountsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Customers::query()
                ->whereNull('deleted_at')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->chunkById(500, function ($customers) {
                    foreach ($customers as $customer) {
                        $wa = preg_replace('/\D/', '', $customer->phone);

                        if (!$wa) {
                            continue;
                        }

                        $exists = CustomerAccount::where('customer_id', $customer->id)
                            ->orWhere('whatsapp_number', $wa)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        CustomerAccount::create([
                            'customer_id' => $customer->id,
                            'name' => $customer->name,
                            'whatsapp_number' => $wa,
                            'password' => Hash::make($wa),
                            'auth_provider' => 'phone',
                            'is_active' => 1,
                        ]);
                    }
                });
        });
    }
}
