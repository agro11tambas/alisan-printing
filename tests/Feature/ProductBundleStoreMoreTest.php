<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductBundleController;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductBundleStoreMoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('conversion_value', 15, 2)->default(1);
            $table->decimal('ratio_value', 15, 2)->default(1);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('bundle_id');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->string('role');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('product_unit_conversions');
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_store_more_product_archives_a_removed_secondary_bundle(): void
    {
        $primary = Products::create(['name' => 'Primary', 'sku' => 'PRI']);
        $keptSecondary = Products::create(['name' => 'Kept', 'sku' => 'KEEP']);
        $removedSecondary = Products::create(['name' => 'Removed', 'sku' => 'REMOVE']);

        $keptBundle = $this->createBundle($primary, $keptSecondary);
        $removedBundle = $this->createBundle($primary, $removedSecondary);

        $request = Request::create('/store-more-product/' . $primary->id, 'POST', [
            'secondary_product_ids' => [$keptSecondary->id],
        ]);

        $response = app(ProductBundleController::class)
            ->storeMoreProduct($request, $primary->id);

        $this->assertSame(url('/erp/products/product-bundles'), $response->getTargetUrl());
        $this->assertNotSoftDeleted($keptBundle->fresh());

        $archivedBundle = ProductBundle::withTrashed()->findOrFail($removedBundle->id);
        $this->assertSoftDeleted($archivedBundle);
        $this->assertStringStartsWith('deleted-', $archivedBundle->sku);
        $this->assertStringEndsWith('-PRIREMOVE', $archivedBundle->sku);
    }

    private function createBundle(Products $primary, Products $secondary): ProductBundle
    {
        $bundle = ProductBundle::create([
            'name' => $primary->name . ' + ' . $secondary->name,
            'sku' => $primary->sku . $secondary->sku,
            'price' => 0,
        ]);

        ProductBundleItem::create([
            'product_id' => $primary->id,
            'bundle_id' => $bundle->id,
            'quantity' => 1,
            'role' => 'primary',
        ]);

        ProductBundleItem::create([
            'product_id' => $secondary->id,
            'bundle_id' => $bundle->id,
            'quantity' => 1,
            'role' => 'secondary',
        ]);

        return $bundle;
    }
}
