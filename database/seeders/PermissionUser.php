<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ambil user dengan id 1
        $user = User::find(1);

        if (!$user) {
            $this->command->warn('User dengan ID 1 tidak ditemukan.');
            return;
        }

        // ambil semua sub items
        $allSubItems = Permission::all();

        if ($allSubItems->isEmpty()) {
            $this->command->warn('Tidak ada Permission Sub Items ditemukan. Jalankan PermissionSubItemSeeder dulu.');
            return;
        }

        // assign semua sub item ke user id 1
        $user->permissions()->syncWithoutDetaching($allSubItems->pluck('id'));

        $this->command->info("User {$user->id} - {$user->name} sudah diberi semua sub permission.");
    }
}
