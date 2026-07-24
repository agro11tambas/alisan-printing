<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CustomerAccountController;
use App\Models\CustomerAccount;
use App\Models\CustomerPasswordResetToken;
use App\Models\Customers;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPhonePasswordLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('google_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
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
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_id')->unique();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_account_customer');
        Schema::dropIfExists('customer_accounts');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_customer_logs_in_with_phone_number_and_password_only(): void
    {
        $customer = Customers::create([
            'name' => 'Customer Test',
            'phone' => '081234567890',
        ]);

        CustomerAccount::create([
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'email' => 'customer@example.com',
            'whatsapp_number' => '6281234567890',
            'password' => Hash::make('password123'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ])->customers()->attach($customer->id);

        $this->postJson('/api/v1/ecommerce/auth/login', [
            'whatsapp_number' => '081234567890',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'customer']]);

        $this->postJson('/api/v1/ecommerce/auth/login', [
            'whatsapp_number' => '6281234567890',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp_number');

        $this->postJson('/api/v1/ecommerce/auth/login', [
            'identifier' => 'customer@example.com',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp_number');
    }

    public function test_customer_can_use_local_phone_format_for_legacy_erp_initial_password(): void
    {
        $customer = Customers::create([
            'name' => 'Legacy ERP Customer',
            'phone' => '6281234567890',
        ]);

        CustomerAccount::create([
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'whatsapp_number' => '6281234567890',
            'password' => Hash::make('6281234567890'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ])->customers()->attach($customer->id);

        $this->postJson('/api/v1/ecommerce/auth/login', [
            'whatsapp_number' => '081234567890',
            'password' => '081234567890',
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_otp_and_google_login_routes_are_removed(): void
    {
        $this->postJson('/api/v1/ecommerce/auth/otp/request', [
            'whatsapp_number' => '081234567890',
        ])->assertNotFound();

        $this->postJson('/api/v1/ecommerce/auth/otp/verify', [
            'whatsapp_number' => '081234567890',
            'otp' => '123456',
        ])->assertNotFound();

        $this->getJson('/api/v1/ecommerce/auth/google/redirect')->assertNotFound();
        $this->getJson('/api/v1/ecommerce/auth/google/callback')->assertNotFound();
    }

    public function test_erp_generates_a_hashed_password_reset_link_valid_for_thirty_minutes(): void
    {
        config(['app.frontend_website_url' => 'https://shop.example.test']);

        $account = CustomerAccount::create([
            'name' => 'Customer Reset',
            'email' => 'reset@example.com',
            'whatsapp_number' => '081298765432',
            'password' => Hash::make('password123'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);
        $this->assertSame('not_created', $account->password_reset_status);

        $response = app(CustomerAccountController::class)
            ->generatePasswordResetLink($account->id);
        $payload = $response->getData(true);

        parse_str(parse_url($payload['data']['reset_url'], PHP_URL_QUERY), $query);
        $storedToken = CustomerPasswordResetToken::where('customer_account_id', $account->id)->firstOrFail();

        $this->assertTrue($payload['success']);
        $this->assertSame('pending', $account->fresh()->password_reset_status);
        $this->assertStringStartsWith(
            'https://shop.example.test/reset-password?token=',
            $payload['data']['reset_url']
        );
        $this->assertSame(hash('sha256', $query['token']), $storedToken->token_hash);
        $this->assertNotSame($query['token'], $storedToken->token_hash);
        $this->assertTrue($storedToken->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));

        $storedToken->update(['expires_at' => now()->subSecond()]);
        $this->assertSame('expired', $account->fresh()->password_reset_status);

        $this->getJson('/api/v1/ecommerce/auth/password-reset/validate?token='.$query['token'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_customer_can_reset_password_once_and_existing_sessions_are_revoked(): void
    {
        $account = CustomerAccount::create([
            'name' => 'Customer Reset',
            'email' => 'reset-once@example.com',
            'whatsapp_number' => '081277777777',
            'password' => Hash::make('old-password'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);
        $account->createToken('existing-session');

        $plainToken = str_repeat('a', 64);
        CustomerPasswordResetToken::create([
            'customer_account_id' => $account->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->getJson('/api/v1/ecommerce/auth/password-reset/validate?token='.$plainToken)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/ecommerce/auth/password-reset', [
            'token' => $plainToken,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password-123', $account->fresh()->password));
        $this->assertNotNull(CustomerPasswordResetToken::firstOrFail()->used_at);
        $this->assertSame('completed', $account->fresh()->password_reset_status);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/ecommerce/auth/password-reset', [
            'token' => $plainToken,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertUnprocessable();
    }

    public function test_logged_in_customer_can_change_password_and_other_sessions_are_revoked(): void
    {
        $account = CustomerAccount::create([
            'name' => 'Customer Password',
            'email' => 'change-password@example.com',
            'whatsapp_number' => '081266666666',
            'password' => Hash::make('current-password'),
            'auth_provider' => 'phone',
            'is_active' => true,
        ]);

        $currentSession = $account->createToken('current-session');
        $otherSession = $account->createToken('other-session');

        $this->withToken($currentSession->plainTextToken)
            ->putJson('/api/v1/ecommerce/auth/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->withToken($currentSession->plainTextToken)
            ->putJson('/api/v1/ecommerce/auth/password', [
                'current_password' => 'current-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password-123', $account->fresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $currentSession->accessToken->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $otherSession->accessToken->id,
        ]);
    }
}
