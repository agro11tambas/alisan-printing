<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PermissionSubItem;

class PermissionSubItemUserSeeder extends Seeder
{
    public function run(): void
    {
        // ambil user dengan id 28
        $user = User::find(1);

        if (!$user) {
            $this->command->warn('User dengan ID 28 tidak ditemukan.');
            return;
        }

        // ambil semua sub items
        $allSubItems = PermissionSubItem::all();

        if ($allSubItems->isEmpty()) {
            $this->command->warn('Tidak ada Permission Sub Items ditemukan. Jalankan PermissionSubItemSeeder dulu.');
            return;
        }

        // assign semua sub item ke user id 28
        $user->permissionSubItems()->syncWithoutDetaching($allSubItems->pluck('id'));

        $this->command->info("User {$user->id} - {$user->name} sudah diberi semua sub permission.");
    }
}
