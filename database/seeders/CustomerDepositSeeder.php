<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerDepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::updateOrCreate(
            [
                'name' => 'Customer Deposit'
            ],
            [
                'type' => 'Customer Deposit',
                'opening_balance' => 0,
                'closing_balance' => 0,
                'is_default' => 0,
            ]
        );
    }
}
