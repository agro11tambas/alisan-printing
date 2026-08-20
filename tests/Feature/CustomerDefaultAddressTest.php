<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CustomerController;
use App\Models\CustomerAccount;
use App\Models\CustomerAddresses;
use App\Models\Customers;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDefaultAddressTest extends TestCase
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

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->decimal('customer_deposit', 15, 2)->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('whatsapp_number')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('auth_provider')->default('phone');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_account_customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_id');
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();
            $table->unique(['customer_account_id', 'customer_id']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('business_name')->nullable();
            $table->text('address');
            $table->text('google_maps')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customer_account_customer');
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_customer_can_change_the_default_address_from_the_website(): void
    {
        [$account, $customer] = $this->createCustomerWithAccount();

        $first = $this->createAddress($customer, 'Pusat', true);
        $second = $this->createAddress($customer, 'Cabang', false);

        Sanctum::actingAs($account);

        $this->putJson('/api/v1/ecommerce/auth/addresses/' . $second->id . '/default')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_customer_cannot_change_the_default_address_of_another_customer(): void
    {
        [$account] = $this->createCustomerWithAccount();

        $otherCustomer = Customers::query()->create(['name' => 'Outlet Orang Lain']);
        $otherAddress = $this->createAddress($otherCustomer, 'Punya Orang Lain', false);

        Sanctum::actingAs($account);

        $this->putJson('/api/v1/ecommerce/auth/addresses/' . $otherAddress->id . '/default')
            ->assertForbidden();

        $this->assertFalse($otherAddress->fresh()->is_default);
    }

    public function test_updating_an_address_does_not_drop_the_default_flag(): void
    {
        [$account, $customer] = $this->createCustomerWithAccount();

        $address = $this->createAddress($customer, 'Pusat', true);

        Sanctum::actingAs($account);

        $this->putJson('/api/v1/ecommerce/auth/addresses/' . $address->id, [
            'address' => 'Alamat yang sudah dikoreksi',
        ])->assertOk();

        $fresh = $address->fresh();

        $this->assertSame('Alamat yang sudah dikoreksi', $fresh->address);
        $this->assertTrue($fresh->is_default);
    }

    public function test_erp_create_marks_the_first_address_as_default(): void
    {
        $this->actingAs(User::factory()->create());

        $request = Request::create('/erp/customers/store', 'POST', [
            'name' => 'Outlet Baru',
            'addresses' => [
                [
                    'business_name' => 'Pusat',
                    'address' => 'Alamat Pusat',
                    'google_maps' => 'https://maps.example.test/pusat',
                ],
                [
                    'business_name' => 'Cabang',
                    'address' => 'Alamat Cabang',
                    'google_maps' => 'https://maps.example.test/cabang',
                ],
            ],
        ]);

        app(CustomerController::class)->store($request);

        $customer = Customers::query()->where('name', 'Outlet Baru')->firstOrFail();
        $defaultAddress = $customer->addresses()->where('is_default', true)->firstOrFail();

        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());
        $this->assertSame('Pusat', $defaultAddress->business_name);
    }

    public function test_erp_edit_keeps_the_default_address_chosen_by_the_customer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customers::query()->create(['name' => 'Outlet Lama', 'user_id' => $user->id]);
        $pusat = $this->createAddress($customer, 'Pusat', false);
        $cabang = $this->createAddress($customer, 'Cabang', true);

        $request = Request::create('/erp/customers/update/' . $customer->id, 'PUT', [
            'name' => 'Outlet Lama',
            'addresses' => [
                [
                    'id' => $pusat->id,
                    'business_name' => 'Pusat',
                    'address' => 'Alamat Pusat',
                    'google_maps' => 'https://maps.example.test/pusat',
                ],
                [
                    'id' => $cabang->id,
                    'business_name' => 'Cabang',
                    'address' => 'Alamat Cabang yang diperbaiki',
                    'google_maps' => 'https://maps.example.test/cabang',
                ],
            ],
        ]);

        app(CustomerController::class)->update($request, $customer->id);

        $addresses = $customer->fresh()->addresses;

        $this->assertSame(1, $addresses->where('is_default', true)->count());
        $this->assertSame('Cabang', $addresses->firstWhere('is_default', true)->business_name);
    }

    public function test_erp_edit_falls_back_to_the_first_address_when_the_default_is_removed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customers::query()->create(['name' => 'Outlet Lama', 'user_id' => $user->id]);
        $pusat = $this->createAddress($customer, 'Pusat', false);
        $this->createAddress($customer, 'Cabang', true);

        $request = Request::create('/erp/customers/update/' . $customer->id, 'PUT', [
            'name' => 'Outlet Lama',
            'addresses' => [
                [
                    'id' => $pusat->id,
                    'business_name' => 'Pusat',
                    'address' => 'Alamat Pusat',
                    'google_maps' => 'https://maps.example.test/pusat',
                ],
            ],
        ]);

        app(CustomerController::class)->update($request, $customer->id);

        $addresses = $customer->fresh()->addresses;

        $this->assertSame(1, $addresses->where('is_default', true)->count());
        $this->assertSame('Pusat', $addresses->firstWhere('is_default', true)->business_name);
    }

    private function createCustomerWithAccount(): array
    {
        $customer = Customers::query()->create(['name' => 'Outlet Customer']);

        $account = CustomerAccount::query()->create([
            'name' => 'Customer Website',
            'whatsapp_number' => '6281234567890',
            'password' => bcrypt('6281234567890'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);

        $account->customers()->attach($customer->id);

        return [$account, $customer];
    }

    private function createAddress(Customers $customer, string $businessName, bool $isDefault): CustomerAddresses
    {
        return $customer->addresses()->create([
            'business_name' => $businessName,
            'address' => 'Alamat ' . $businessName,
            'google_maps' => 'https://maps.example.test/' . strtolower($businessName),
            'is_default' => $isDefault,
        ]);
    }
}
