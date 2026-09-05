<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Pecah modul Design jadi dua sub menu: Design dan Design Customer.
 *
 * Sebelum ini modul Design tidak punya sub item sama sekali, jadi semua user
 * yang sudah pegang permission 'design' otomatis diberi kedua sub item —
 * kalau tidak, menu Design mereka langsung hilang begitu migrasi ini jalan.
 */
return new class extends Migration
{
    private const MODULE_SLUG = 'design';

    private const SUB_ITEMS = [
        ['name' => 'Design', 'slug' => 'design-list'],
        ['name' => 'Design Customer', 'slug' => 'design-customers'],
    ];

    public function up(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')->where('slug', self::MODULE_SLUG)->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Design Module',
                'slug' => self::MODULE_SLUG,
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

        $userIds = DB::table('permission_users')
            ->where('permission_id', $permissionId)
            ->pluck('user_id');

        // Slug hak akses di-cache per user, jadi tulis-langsung ke tabel pivot
        // harus dibarengi buang cache — kalau tidak menunya baru muncul setelah
        // cache kedaluwarsa sendiri.
        $this->forgetPermissionCache($userIds);

        foreach ($userIds as $userId) {
            foreach ($subItemIds as $subItemId) {
                $exists = DB::table('permission_sub_item_users')
                    ->where('user_id', $userId)
                    ->where('permission_sub_item_id', $subItemId)
                    ->exists();

                if (! $exists) {
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
        $slugs = array_column(self::SUB_ITEMS, 'slug');

        $subItemIds = DB::table('permission_sub_items')->whereIn('slug', $slugs)->pluck('id');

        if ($subItemIds->isEmpty()) {
            return;
        }

        $this->forgetPermissionCache(
            DB::table('permission_sub_item_users')->whereIn('permission_sub_item_id', $subItemIds)->pluck('user_id')
        );

        DB::table('permission_sub_item_users')->whereIn('permission_sub_item_id', $subItemIds)->delete();
        DB::table('permission_sub_items')->whereIn('id', $subItemIds)->delete();
    }
};
