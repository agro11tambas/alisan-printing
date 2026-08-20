<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CustomerAccountController;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerAccountRenameTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_rename_is_allowed_when_the_number_is_not_changed(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->createCustomerAccount('Nama Lama', '081234567890');

        $request = Request::create('/erp/customer-accounts/update/'.$account->id, 'PUT', [
            'name' => 'Nama Baru',
            'whatsapp_number' => '081234567890',
            'is_active' => 1,
        ]);

        app(CustomerAccountController::class)->update($request, $account->id);

        $this->assertSame('Nama Baru', $account->fresh()->name);
    }

    public function test_rename_is_allowed_even_when_another_account_holds_an_equivalent_number(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->createCustomerAccount('Nama Lama', '081234567890');
        $this->createCustomerAccount('Akun Kembar', '6281234567890');

        $request = Request::create('/erp/customer-accounts/update/'.$account->id, 'PUT', [
            'name' => 'Nama Baru',
            'whatsapp_number' => '081234567890',
            'is_active' => 1,
        ]);

        app(CustomerAccountController::class)->update($request, $account->id);

        $fresh = $account->fresh();

        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame('081234567890', $fresh->whatsapp_number);
    }

    public function test_changing_the_number_to_one_owned_by_another_account_is_still_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->createCustomerAccount('Nama Lama', '6281111111111');
        $this->createCustomerAccount('Akun Lain', '6282222222222');

        $request = Request::create('/erp/customer-accounts/update/'.$account->id, 'PUT', [
            'name' => 'Nama Baru',
            'whatsapp_number' => '082222222222',
            'is_active' => 1,
        ]);

        try {
            app(CustomerAccountController::class)->update($request, $account->id);
            $this->fail('Nomor milik account lain seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('whatsapp_number', $exception->errors());
        }

        $this->assertSame('Nama Lama', $account->fresh()->name);
    }

    public function test_number_is_normalised_when_it_is_changed(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->createCustomerAccount('Nama Lama', '6281111111111');

        $request = Request::create('/erp/customer-accounts/update/'.$account->id, 'PUT', [
            'name' => 'Nama Lama',
            'whatsapp_number' => '083399988877',
            'is_active' => 1,
        ]);

        app(CustomerAccountController::class)->update($request, $account->id);

        $this->assertSame('6283399988877', $account->fresh()->whatsapp_number);
    }

    private function createCustomerAccount(string $name, string $phone): CustomerAccount
    {
        return CustomerAccount::query()->create([
            'name' => $name,
            'whatsapp_number' => $phone,
            'password' => bcrypt($phone),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);
    }
}
