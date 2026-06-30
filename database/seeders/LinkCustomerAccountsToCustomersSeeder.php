<?php

namespace Database\Seeders;

use App\Models\Customers;
use App\Models\CustomerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinkCustomerAccountsToCustomersSeeder extends Seeder
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
                        $phone = preg_replace('/\D/', '', $customer->phone);

                        if (!$phone) {
                            continue;
                        }

                        $account = CustomerAccount::query()
                            ->whereNull('deleted_at')
                            ->where('whatsapp_number', $phone)
                            ->first();

                        if (!$account) {
                            continue;
                        }

                        $customer->accounts()->syncWithoutDetaching([
                            $account->id => [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ],
                        ]);
                    }
                });
        });
    }
}
