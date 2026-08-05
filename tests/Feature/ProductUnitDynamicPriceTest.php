<?php

namespace Tests\Feature;

use App\Models\PriceMode;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use App\Models\ProductUnitConversion;
use App\Http\Controllers\Admin\ProductsController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ProductBundlePricingService;
use Tests\TestCase;

class ProductUnitDynamicPriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('price_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        PriceMode::insert([
            ['name' => 'Polos', 'slug' => 'polosan', 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Sablon', 'slug' => 'sablon', 'is_active' => true, 'sort_order' => 20],
            ['name' => 'Printing', 'slug' => 'printing', 'is_active' => true, 'sort_order' => 30],
        ]);

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('conversion_value', 15, 2)->default(1);
            $table->decimal('ratio_value', 15, 4)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundle_id');
            $table->unsignedBigInteger('product_id');
            $table->string('role')->default('secondary');
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundle_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_bundle_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('conversion_value', 15, 2)->default(1);
            $table->decimal('ratio_value', 15, 4)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamps();
            $table->unique(['product_bundle_id', 'unit_id']);
        });

        Schema::create('product_bundle_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_bundle_unit_conversion_id');
            $table->unsignedBigInteger('price_mode_id');
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_bundle_unit_conversion_id', 'price_mode_id']);
        });


        Schema::create('product_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_unit_conversion_id');
            $table->unsignedBigInteger('price_mode_id');
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['product_unit_conversion_id', 'price_mode_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('product_bundle_unit_prices');
        Schema::dropIfExists('product_bundle_unit_conversions');
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_unit_prices');
        Schema::dropIfExists('product_unit_conversions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('price_modes');
        parent::tearDown();
    }

    public function test_one_unit_can_have_a_price_for_each_master_mode(): void
    {
        $conversion = ProductUnitConversion::create([
            'product_id' => 10,
            'unit_id' => 2,
            'conversion_value' => 1,
            'fixed_cost' => 200,
        ]);
        $modeIds = PriceMode::pluck('id', 'slug');

        $conversion->prices()->createMany([
            ['price_mode_id' => $modeIds['polosan'], 'fixed_cost' => 200, 'margin' => 50, 'sale_price' => 250],
            ['price_mode_id' => $modeIds['sablon'], 'fixed_cost' => 200, 'margin' => 200, 'sale_price' => 400],
            ['price_mode_id' => $modeIds['printing'], 'fixed_cost' => 200, 'margin' => 300, 'sale_price' => 500],
        ]);

        $prices = $conversion->fresh('prices.priceMode')->prices
            ->keyBy(fn ($price) => $price->priceMode->slug);

        $this->assertCount(3, $prices);
        $this->assertSame('250.00', $prices['polosan']->sale_price);
        $this->assertSame('400.00', $prices['sablon']->sale_price);
        $this->assertSame('500.00', $prices['printing']->sale_price);
    }

    public function test_dynamic_price_partial_renders_master_mode_input(): void
    {
        $html = view('erp.pages.products.partials.dynamic-prices', [
            'productUnits' => collect(),
            'priceModes' => PriceMode::orderBy('sort_order')->get(),
        ])->render();

        $this->assertStringContainsString('name="prices[0][price_mode_id]"', $html);
        $this->assertStringContainsString('Polos', $html);
        $this->assertStringContainsString('Sablon', $html);
        $this->assertStringContainsString('Printing', $html);
        $this->assertStringContainsString("addButton.addEventListener('click'", $html);
        $this->assertMatchesRegularExpression('/dynamic-fixed-cost[^>]*readonly/', $html);
        $this->assertStringContainsString('queueMicrotask(syncUnitFixedCosts)', $html);
        $this->assertStringContainsString('event.target.value = formatMoney(numberValue(event.target.value))', $html);
        $this->assertLessThan(
            strpos($html, 'const numberValue'),
            strpos($html, 'const availablePriceModes')
        );
    }

    public function test_dynamic_price_uses_product_unit_fixed_cost_and_ignores_submitted_total(): void
    {
        $product = Products::create([
            'name' => 'Kaos',
            'sku' => 'KAOS-001',
        ]);
        $conversion = ProductUnitConversion::create([
            'product_id' => $product->id,
            'unit_id' => 2,
            'conversion_value' => 1,
            'fixed_cost' => 200,
        ]);
        $mode = PriceMode::where('slug', 'sablon')->firstOrFail();

        $method = new \ReflectionMethod(ProductsController::class, 'syncDynamicPrices');
        $method->setAccessible(true);
        $method->invoke(app(ProductsController::class), $product, [[
            'unit_id' => $conversion->unit_id,
            'price_mode_id' => $mode->id,
            'fixed_cost' => 999,
            'margin' => 50,
            'sale_price' => 9999,
        ]]);

        $price = $conversion->prices()->where('price_mode_id', $mode->id)->firstOrFail();

        $this->assertSame('200.00', $price->fixed_cost);
        $this->assertSame('50.00', $price->margin);
        $this->assertSame('250.00', $price->sale_price);
    }
    public function test_bundle_prices_are_merged_by_unit_with_polosan_fallback(): void
    {
        DB::table('product_units')->insert([
            ['id' => 1, 'name' => 'Dus'],
            ['id' => 2, 'name' => 'Pcs'],
        ]);
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Product 1', 'sku' => 'P1', 'base_unit_id' => 1],
            ['id' => 2, 'name' => 'Product 2', 'sku' => 'P2', 'base_unit_id' => 1],
        ]);

        $conversions = collect([
            ['product_id' => 1, 'unit_id' => 1, 'conversion_value' => 1, 'ratio_value' => 1],
            ['product_id' => 1, 'unit_id' => 2, 'conversion_value' => 1, 'ratio_value' => 1000],
            ['product_id' => 2, 'unit_id' => 1, 'conversion_value' => 1, 'ratio_value' => 1],
            ['product_id' => 2, 'unit_id' => 2, 'conversion_value' => 1, 'ratio_value' => 1000],
        ])->map(fn ($attributes) => ProductUnitConversion::create($attributes));
        $modes = PriceMode::pluck('id', 'slug');

        $conversions[0]->prices()->create([
            'price_mode_id' => $modes['polosan'], 'fixed_cost' => 200000, 'margin' => 150000, 'sale_price' => 350000,
        ]);
        $conversions[1]->prices()->createMany([
            ['price_mode_id' => $modes['polosan'], 'fixed_cost' => 200, 'margin' => 150, 'sale_price' => 350],
            ['price_mode_id' => $modes['sablon'], 'fixed_cost' => 200, 'margin' => 300, 'sale_price' => 500],
            ['price_mode_id' => $modes['printing'], 'fixed_cost' => 200, 'margin' => 400, 'sale_price' => 600],
        ]);
        $conversions[2]->prices()->create([
            'price_mode_id' => $modes['polosan'], 'fixed_cost' => 150000, 'margin' => 50000, 'sale_price' => 200000,
        ]);
        $conversions[3]->prices()->create([
            'price_mode_id' => $modes['polosan'], 'fixed_cost' => 150, 'margin' => 50, 'sale_price' => 200,
        ]);

        $bundle = ProductBundle::create(['name' => 'Product 1 + Product 2', 'sku' => 'P1P2', 'price' => 0]);
        ProductBundleItem::create(['bundle_id' => $bundle->id, 'product_id' => 1, 'role' => 'primary']);
        ProductBundleItem::create(['bundle_id' => $bundle->id, 'product_id' => 2, 'role' => 'secondary']);

        app(ProductBundlePricingService::class)->sync($bundle);

        $prices = $bundle->fresh('unitConversions.unit', 'unitConversions.prices.priceMode')
            ->unitConversions
            ->flatMap(fn ($unit) => $unit->prices->mapWithKeys(fn ($price) => [
                $price->priceMode->slug.'|'.$unit->unit_id => $price,
            ]));

        $this->assertCount(4, $prices);
        $this->assertSame('350000.00', $prices['polosan|1']->fixed_cost);
        $this->assertSame('200000.00', $prices['polosan|1']->margin);
        $this->assertSame('550000.00', $prices['polosan|1']->sale_price);
        $this->assertSame('350.00', $prices['polosan|2']->fixed_cost);
        $this->assertSame('200.00', $prices['polosan|2']->margin);
        $this->assertSame('550.00', $prices['polosan|2']->sale_price);
        $this->assertSame('350.00', $prices['sablon|2']->fixed_cost);
        $this->assertSame('350.00', $prices['sablon|2']->margin);
        $this->assertSame('700.00', $prices['sablon|2']->sale_price);
        $this->assertSame('350.00', $prices['printing|2']->fixed_cost);
        $this->assertSame('450.00', $prices['printing|2']->margin);
        $this->assertSame('800.00', $prices['printing|2']->sale_price);
    }
    public function test_order_item_mode_migration_accepts_sablon(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->enum('mode', ['printing', 'polosan'])->default('printing');
        });

        $migration = require database_path('migrations/2026_08_04_000002_allow_sablon_order_item_mode.php');
        $migration->up();
        DB::table('order_items')->insert(['mode' => 'sablon']);

        $this->assertDatabaseHas('order_items', ['mode' => 'sablon']);
    }
}
