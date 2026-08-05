<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerAccountController;
use App\Models\CustomerAccount;
use App\Models\Customers;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerExistingAccountLinkTest extends TestCase
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
            $table->text('google_maps');
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

    public function test_create_customer_links_matching_existing_account_instead_of_creating_duplicate(): void
    {
        $user = User::factory()->create();
        $account = $this->createCustomerAccount('6281234567890');
        $this->actingAs($user);

        $request = Request::create('/erp/customers/store', 'POST', [
            'name' => 'Outlet Baru',
            'accounts' => [[
                'name' => 'Nama Input Manual',
                'whatsapp_number' => '081234567890',
            ]],
            'addresses' => [[
                'business_name' => 'Pusat',
                'address' => 'Jl. Contoh No. 1',
                'google_maps' => 'https://maps.example.test/outlet-baru',
            ]],
        ]);

        app(CustomerController::class)->store($request);

        $customer = Customers::query()->where('name', 'Outlet Baru')->firstOrFail();

        $this->assertTrue($customer->accounts()->whereKey($account->id)->exists());
        $this->assertSame(1, CustomerAccount::query()->count());
    }


    public function test_create_customer_marks_only_the_selected_address_as_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/erp/customers/store', 'POST', [
            'name' => 'Outlet Dengan Cabang',
            'primary_address_index' => 1,
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

        $customer = Customers::query()->where('name', 'Outlet Dengan Cabang')->firstOrFail();
        $defaultAddress = $customer->addresses()->where('is_default', true)->firstOrFail();

        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());
        $this->assertSame('Cabang', $defaultAddress->business_name);
    }

    public function test_edit_customer_links_matching_existing_account_instead_of_creating_duplicate(): void
    {
        $user = User::factory()->create();
        $account = $this->createCustomerAccount('081298765432');
        $customer = Customers::query()->create([
            'name' => 'Outlet Lama',
            'user_id' => $user->id,
        ]);
        $customer->addresses()->create([
            'business_name' => 'Lama',
            'address' => 'Alamat Lama',
            'google_maps' => 'https://maps.example.test/lama',
        ]);
        $this->actingAs($user);

        $request = Request::create('/erp/customers/update/' . $customer->id, 'PUT', [
            'name' => 'Outlet Diperbarui',
            'accounts' => [[
                'name' => 'Nama Input Manual',
                'whatsapp_number' => '6281298765432',
            ]],
            'addresses' => [[
                'business_name' => 'Baru',
                'address' => 'Alamat Baru',
                'google_maps' => 'https://maps.example.test/baru',
            ]],
        ]);

        app(CustomerController::class)->update($request, $customer->id);

        $this->assertTrue($customer->fresh()->accounts()->whereKey($account->id)->exists());
        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_editing_selected_account_to_an_existing_number_replaces_the_customer_link(): void
    {
        $user = User::factory()->create();
        $selectedAccount = $this->createCustomerAccount('6281111111111');
        $matchingAccount = $this->createCustomerAccount('6282222222222');
        $customer = Customers::query()->create([
            'name' => 'Outlet Lama',
            'user_id' => $user->id,
        ]);
        $customer->accounts()->attach($selectedAccount->id);
        $customer->addresses()->create([
            'business_name' => 'Lama',
            'address' => 'Alamat Lama',
            'google_maps' => 'https://maps.example.test/lama',
        ]);
        $this->actingAs($user);

        $request = Request::create('/erp/customers/update/' . $customer->id, 'PUT', [
            'name' => 'Outlet Diperbarui',
            'existing_account_ids' => [$selectedAccount->id],
            'existing_accounts' => [
                $selectedAccount->id => [
                    'name' => 'Nama Input Manual',
                    'whatsapp_number' => '082222222222',
                ],
            ],
            'addresses' => [[
                'business_name' => 'Baru',
                'address' => 'Alamat Baru',
                'google_maps' => 'https://maps.example.test/baru',
            ]],
        ]);

        app(CustomerController::class)->update($request, $customer->id);

        $linkedAccountIds = $customer->fresh()->accounts()->pluck('customer_accounts.id')->all();

        $this->assertSame([$matchingAccount->id], $linkedAccountIds);
        $this->assertSame('Account Existing', $matchingAccount->fresh()->name);
        $this->assertSame(2, CustomerAccount::query()->count());
    }

    public function test_customer_account_creation_rejects_equivalent_local_and_international_numbers(): void
    {
        $this->createCustomerAccount('628111222333');

        $request = Request::create('/erp/customer-accounts/store', 'POST', [
            'name' => 'Account Duplikat',
            'whatsapp_number' => '08111222333',
            'is_active' => true,
        ]);

        try {
            app(CustomerAccountController::class)->store($request);
            $this->fail('Equivalent phone number should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('whatsapp_number', $exception->errors());
        }

        $this->assertSame(1, CustomerAccount::query()->count());
    }
    public function test_new_customer_account_is_stored_in_canonical_62_format(): void
    {
        $request = Request::create('/erp/customer-accounts/store', 'POST', [
            'name' => 'Account Baru',
            'whatsapp_number' => '081377788899',
            'is_active' => true,
        ]);

        app(CustomerAccountController::class)->store($request);

        $this->assertDatabaseHas('customer_accounts', [
            'name' => 'Account Baru',
            'whatsapp_number' => '6281377788899',
        ]);
    }
    private function createCustomerAccount(string $phone): CustomerAccount
    {
        return CustomerAccount::query()->create([
            'name' => 'Account Existing',
            'whatsapp_number' => $phone,
            'password' => bcrypt($phone),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);
    }
}