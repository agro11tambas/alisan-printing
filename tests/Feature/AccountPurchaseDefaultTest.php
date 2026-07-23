<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountController;
use App\Models\Account;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountPurchaseDefaultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_default_purchase')->default(false);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('accounts');

        parent::tearDown();
    }

    public function test_purchase_default_is_unique_and_independent_from_sale_default(): void
    {
        $saleDefault = Account::create([
            'name' => 'Cash',
            'type' => 'Kas Utama',
            'is_default' => true,
        ]);
        $firstPurchase = Account::create([
            'name' => 'Bank',
            'type' => 'Bank A',
        ]);
        $secondPurchase = Account::create([
            'name' => 'Bank',
            'type' => 'Bank B',
        ]);

        $controller = app(AccountController::class);
        $controller->markAsDefaultPurchase($firstPurchase->id);
        $controller->markAsDefaultPurchase($secondPurchase->id);

        $this->assertTrue($saleDefault->fresh()->is_default);
        $this->assertFalse($saleDefault->fresh()->is_default_purchase);
        $this->assertFalse($firstPurchase->fresh()->is_default_purchase);
        $this->assertTrue($secondPurchase->fresh()->is_default_purchase);
        $this->assertSame(1, Account::where('is_default_purchase', true)->count());
    }
}
