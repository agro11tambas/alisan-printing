<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Alisan',
                'username' => 'alisan',
                'email' => 'alisan@example.com',
                'password' => Hash::make('adminalisan@1122'),
                'role' => 'Owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
