<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CustomerAccountController;
use App\Models\CustomerAccount;
use App\Models\CustomerLoginLink;
use App\Models\Customers;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerLoginLinkWithoutAccountTest extends TestCase
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

        Schema::create('customer_login_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_id');
            $table->string('code_hash', 64)->unique();
            $table->boolean('single_use')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_login_links');
        Schema::dropIfExists('customer_account_customer');
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_generating_link_for_customer_without_account_creates_account_and_link(): void
    {
        $customer = Customers::create(['name' => 'Outlet Tanpa Akun', 'phone' => '081234567890']);

        $response = $this->generate($customer);

        $this->assertSame(200, $response->getStatusCode());

        $account = CustomerAccount::query()->sole();
        $this->assertSame('Outlet Tanpa Akun', $account->name);
        $this->assertSame('6281234567890', $account->whatsapp_number);
        $this->assertTrue((bool) $account->is_active);
        $this->assertTrue($customer->accounts()->whereKey($account->id)->exists());

        $loginUrl = $response->getData(true)['data']['login_url'];
        $code = substr($loginUrl, strrpos($loginUrl, '/m/') + 3);
        $this->assertTrue(
            CustomerLoginLink::query()
                ->where('customer_account_id', $account->id)
                ->where('code_hash', CustomerLoginLink::hashCode($code))
                ->exists()
        );
    }

    public function test_existing_account_with_same_phone_is_linked_instead_of_duplicated(): void
    {
        $existing = CustomerAccount::create([
            'name' => 'Akun Lama',
            'whatsapp_number' => '6281234567890',
            'password' => bcrypt('6281234567890'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);
        $customer = Customers::create(['name' => 'Outlet Baru', 'phone' => '081234567890']);

        $response = $this->generate($customer);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, CustomerAccount::query()->count());
        $this->assertTrue($customer->accounts()->whereKey($existing->id)->exists());
        $this->assertSame(1, CustomerLoginLink::query()->where('customer_account_id', $existing->id)->count());
    }

    public function test_customer_without_phone_is_rejected_without_creating_account(): void
    {
        $customer = Customers::create(['name' => 'Outlet Tanpa Nomor', 'phone' => null]);

        $response = $this->generate($customer);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, CustomerAccount::query()->count());
        $this->assertSame(0, CustomerLoginLink::query()->count());
    }

    private function generate(Customers $customer)
    {
        $request = Request::create('/erp/customers/'.$customer->id.'/login-link', 'POST', [
            'expires_in_hours' => null,
            'single_use' => false,
        ]);

        return app(CustomerAccountController::class)->generateLoginLinkForCustomer($request, $customer->id);
    }
}
