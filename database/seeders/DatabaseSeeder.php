<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermissionSeeder::class,
            PermissionUser::class,
            PermissionSubItemSeeder::class,
            PermissionSubItemUserSeeder::class,
            // Tambahkan seeder lain di sini
        ]);
    }
}
