<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'id' => 11,
                'name' => 'Sale',
                'type' => 'Sale Account',
                'description' => 'Account for recording sales orders',
            ],
            [
                'id' => 13,
                'name' => 'Sale',
                'type' => 'Sale Return',
                'description' => 'Account for recording sale returns (refunds)',
            ],
            [
                'id' => 12,
                'name' => 'Purchase',
                'type' => 'Purchase Account',
                'description' => 'Account for recording Purchase transactions',
            ],
            [
                'id' => 14,
                'name' => 'Purchase',
                'type' => 'Purchase Return',
                'description' => 'Account for recording Purchase transactions',
            ],
            [
                'id' => 10,
                'name' => 'Capital',
                'type' => 'Owner Contribution',
                'description' => 'Account for recording Capital transactions',
            ],
            [
                'id' => 15,
                'name' => 'Capital',
                'type' => 'Withdraw Money',
                'description' => 'Account for recording Capital transactions',
            ],
            [
                'id' => 7,
                'name' => 'Cash',
                'type' => 'Cash',
                'description' => 'Account for recording Cash transactions',
            ],
            [
                'id' => 9,
                'name' => 'Bank',
                'type' => 'Bank',
                'description' => 'Account for recording Bank transactions',
            ],
        ];

        foreach ($accounts as $acc) {
            Account::firstOrCreate(
                ['id' => $acc['id']],
                [
                    'name'        => $acc['name'],
                    'type'        => $acc['type'],
                ]
            );
        }
    }
}
