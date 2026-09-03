<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Daftarkan modul HPP FIFO ke daftar hak akses.
 *
 * Halaman Shop Manager membaca tabel permissions secara dinamis, jadi begitu
 * baris ini ada, checkbox-nya langsung muncul di layar Edit Shop Manager.
 *
 * Akses awal diberikan ke user ber-role Owner supaya modulnya tidak "hilang"
 * setelah deploy; role lain diatur manual lewat layar Shop Manager.
 */
return new class extends Migration
{
    private const MODULE = ['name' => 'HPP FIFO Module', 'slug' => 'fifo-cost'];

    private const SUB_ITEMS = [
        ['name' => 'Batch Purchase (Snapshot)', 'slug' => 'cost-layers'],
        ['name' => 'Rincian HPP Penjualan', 'slug' => 'cost-consumptions'],
    ];

    private const AUTO_GRANT_ROLES = ['Owner'];

    public function up(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')->where('slug', self::MODULE['slug'])->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId(self::MODULE + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $subItemIds = [];

        foreach (self::SUB_ITEMS as $subItem) {
            $id = DB::table('permission_sub_items')->where('slug', $subItem['slug'])->value('id');

            if ($id === null) {
                $id = DB::table('permission_sub_items')->insertGetId($subItem + [
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $subItemIds[] = $id;
        }

        $ownerIds = DB::table('users')
            ->whereIn('role', self::AUTO_GRANT_ROLES)
            ->whereNull('deleted_at')
            ->pluck('id');

        // Hak akses di-cache 10 menit per user. Karena migrasi ini menulis
        // langsung ke tabel pivot, cache-nya harus dibuang manual — kalau tidak,
        // menu baru tidak muncul sampai cache-nya kedaluwarsa sendiri.
        $this->forgetPermissionCache($ownerIds);

        foreach ($ownerIds as $userId) {
            $exists = DB::table('permission_users')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('permission_users')->insert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($subItemIds as $subItemId) {
                $subExists = DB::table('permission_sub_item_users')
                    ->where('user_id', $userId)
                    ->where('permission_sub_item_id', $subItemId)
                    ->exists();

                if (! $subExists) {
                    DB::table('permission_sub_item_users')->insert([
                        'user_id' => $userId,
                        'permission_sub_item_id' => $subItemId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Buang cache slug hak akses milik user-user ini.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $userIds
     */
    private function forgetPermissionCache($userIds): void
    {
        foreach ($userIds as $userId) {
            Cache::forget("user:{$userId}:permissions");
            Cache::forget("user:{$userId}:sub-permissions");
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', self::MODULE['slug'])->value('id');

        if ($permissionId === null) {
            return;
        }

        $this->forgetPermissionCache(
            DB::table('permission_users')->where('permission_id', $permissionId)->pluck('user_id')
        );

        $subItemIds = DB::table('permission_sub_items')
            ->where('permission_id', $permissionId)
            ->pluck('id');

        DB::table('permission_sub_item_users')->whereIn('permission_sub_item_id', $subItemIds)->delete();
        DB::table('permission_sub_items')->where('permission_id', $permissionId)->delete();
        DB::table('permission_users')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
