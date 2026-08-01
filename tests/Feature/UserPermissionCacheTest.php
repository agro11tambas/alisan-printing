<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionSubItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserPermissionCacheTest extends TestCase
{


    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permission_sub_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permission_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('permission_sub_item_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_sub_item_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('permission_sub_item_users');
        Schema::dropIfExists('permission_users');
        Schema::dropIfExists('permission_sub_items');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }
    public function test_permission_checks_are_cached_and_can_be_invalidated(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create([
            'name' => 'Dashboard',
            'slug' => 'dashboard',
        ]);
        $subPermission = PermissionSubItem::create([
            'permission_id' => $permission->id,
            'name' => 'Product list',
            'slug' => 'product-list',
        ]);

        $user->permissions()->sync([$permission->id]);
        $user->permissionSubItems()->sync([$subPermission->id]);

        $checker = $user->fresh();
        $this->assertTrue($checker->hasPermission('dashboard'));
        $this->assertTrue($checker->hasSubPermission('product-list'));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertTrue($checker->hasPermission('dashboard'));
        $this->assertTrue($checker->hasSubPermission('product-list'));
        $this->assertSame([], DB::getQueryLog());

        $user->permissions()->sync([]);
        $user->permissionSubItems()->sync([]);
        $checker->forgetPermissionCache();

        $freshChecker = $user->fresh();
        $this->assertFalse($freshChecker->hasPermission('dashboard'));
        $this->assertFalse($freshChecker->hasSubPermission('product-list'));
    }
}
