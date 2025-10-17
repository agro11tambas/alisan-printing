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
                'name' => 'Sale',
                'type' => 'Sale Account',
                'description' => 'Account for recording sales orders',
            ],
            [
                'name' => 'Sale',
                'type' => 'Sale Return',
                'description' => 'Account for recording sale returns (refunds)',
            ],
            [
                'name' => 'Purchase',
                'type' => 'Purchase Account',
                'description' => 'Account for recording Purchase transactions',
            ],
            [
                'name' => 'Purchase',
                'type' => 'Purchase Return',
                'description' => 'Account for recording Purchase transactions',
            ],
            [
                'name' => 'Capital',
                'type' => 'Owner Contribution',
                'description' => 'Account for recording Capital transactions',
            ],
            [
                'name' => 'Capital',
                'type' => 'Withdraw Money',
                'description' => 'Account for recording Capital transactions',
            ],
        ];

        foreach ($accounts as $acc) {
            Account::firstOrCreate(
                ['name' => $acc['name'], 'type' => $acc['type']],
            );
        }

        echo "✅ AccountSeeder selesai — akun dasar termasuk 'Sale Return' sudah dibuat.\n";
    }
}
