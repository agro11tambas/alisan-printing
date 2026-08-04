<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('price_modes')) {
            Schema::create('price_modes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        foreach ([
            ['name' => 'Polos', 'slug' => 'polosan', 'sort_order' => 10],
            ['name' => 'Sablon', 'slug' => 'sablon', 'sort_order' => 20],
            ['name' => 'Printing', 'slug' => 'printing', 'sort_order' => 30],
        ] as $mode) {
            DB::table('price_modes')->updateOrInsert(
                ['slug' => $mode['slug']],
                $mode + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        if (!Schema::hasColumn('product_unit_prices', 'price_mode_id')) {
            Schema::table('product_unit_prices', function (Blueprint $table) {
                $table->foreignId('price_mode_id')->nullable()->after('product_unit_conversion_id')
                    ->constrained('price_modes')->restrictOnDelete();
            });
        }

        if (!Schema::hasColumn('order_items', 'price_mode_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('price_mode_id')->nullable()->after('mode')
                    ->constrained('price_modes')->nullOnDelete();
            });
        }

        $modeIds = DB::table('price_modes')->pluck('id', 'slug');
        foreach ($modeIds as $slug => $id) {
            if (Schema::hasColumn('product_unit_prices', 'mode')) {
                DB::table('product_unit_prices')->where('mode', $slug)->update(['price_mode_id' => $id]);
            }
            DB::table('order_items')->where('mode', $slug)->update(['price_mode_id' => $id]);
        }

        if (Schema::hasColumn('product_unit_prices', 'mode')) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $indexes = collect(DB::select('SHOW INDEX FROM product_unit_prices'))->pluck('Key_name')->unique();
                if (!$indexes->contains('product_unit_prices_conversion_index')) {
                    DB::statement('ALTER TABLE product_unit_prices ADD INDEX product_unit_prices_conversion_index (product_unit_conversion_id)');
                }
                if ($indexes->contains('product_unit_prices_product_unit_conversion_id_mode_unique')) {
                    DB::statement('ALTER TABLE product_unit_prices DROP INDEX product_unit_prices_product_unit_conversion_id_mode_unique');
                }
                if (!$indexes->contains('pup_conversion_mode_unique')) {
                    DB::statement('ALTER TABLE product_unit_prices ADD UNIQUE pup_conversion_mode_unique (product_unit_conversion_id, price_mode_id)');
                }
            } else {
                Schema::table('product_unit_prices', function (Blueprint $table) {
                    $table->index('product_unit_conversion_id', 'product_unit_prices_conversion_index');
                    $table->dropUnique(['product_unit_conversion_id', 'mode']);
                    $table->unique(['product_unit_conversion_id', 'price_mode_id'], 'pup_conversion_mode_unique');
                });
            }

            Schema::table('product_unit_prices', function (Blueprint $table) {
                $table->dropColumn('mode');
            });
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('permission_sub_items')) {
            $productsPermissionId = DB::table('permissions')->where('slug', 'products')->value('id');
            if ($productsPermissionId) {
                DB::table('permission_sub_items')->updateOrInsert(
                    ['slug' => 'price-modes'],
                    [
                        'permission_id' => $productsPermissionId,
                        'name' => 'Mode',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
                $priceModePermissionId = DB::table('permission_sub_items')
                    ->where('slug', 'price-modes')->value('id');

                if ($priceModePermissionId && Schema::hasTable('permission_sub_item_users')) {
                    $productUnitPermissionId = DB::table('permission_sub_items')
                        ->where('slug', 'product-units')->value('id');
                    if ($productUnitPermissionId) {
                        $rows = DB::table('permission_sub_item_users')
                            ->where('permission_sub_item_id', $productUnitPermissionId)
                            ->pluck('user_id')
                            ->map(fn ($userId) => [
                                'user_id' => $userId,
                                'permission_sub_item_id' => $priceModePermissionId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ])->all();
                        if ($rows) DB::table('permission_sub_item_users')->insertOrIgnore($rows);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('product_unit_prices', 'mode')) {
            Schema::table('product_unit_prices', function (Blueprint $table) {
                $table->string('mode', 30)->nullable()->after('product_unit_conversion_id');
            });
            DB::table('product_unit_prices')
                ->join('price_modes', 'price_modes.id', '=', 'product_unit_prices.price_mode_id')
                ->update(['product_unit_prices.mode' => DB::raw('price_modes.slug')]);
        }

        Schema::table('product_unit_prices', function (Blueprint $table) {
            $table->dropUnique('pup_conversion_mode_unique');
            $table->unique(['product_unit_conversion_id', 'mode']);
            $table->dropConstrainedForeignId('price_mode_id');
        });
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('price_mode_id'));
        DB::table('permission_sub_items')->where('slug', 'price-modes')->delete();
        Schema::dropIfExists('price_modes');
    }
};
