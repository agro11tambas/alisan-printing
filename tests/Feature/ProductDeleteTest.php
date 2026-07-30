<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductsController;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('bundle_id');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->string('role')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('product_bundles');
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_delete_archives_non_nullable_skus_and_soft_deletes_related_bundle(): void
    {
        $product = Products::create([
            'name' => 'Product 2',
            'sku' => 'pr2',
        ]);

        $bundle = ProductBundle::create([
            'name' => 'Bundle Product 2',
            'sku' => 'bundle-pr2',
        ]);

        $bundleItem = ProductBundleItem::create([
            'product_id' => $product->id,
            'bundle_id' => $bundle->id,
            'quantity' => 1,
            'role' => 'primary',
        ]);

        $response = app(ProductsController::class)->delete($product->id);

        $this->assertSame(url('/erp/products'), $response->getTargetUrl());

        $deletedProduct = Products::withTrashed()->findOrFail($product->id);
        $deletedBundle = ProductBundle::withTrashed()->findOrFail($bundle->id);

        $this->assertSoftDeleted($deletedProduct);
        $this->assertSoftDeleted($deletedBundle);
        $this->assertSoftDeleted('product_bundle_items', ['id' => $bundleItem->id]);
        $this->assertStringStartsWith('deleted-', $deletedProduct->sku);
        $this->assertStringEndsWith('-pr2', $deletedProduct->sku);
        $this->assertStringStartsWith('deleted-', $deletedBundle->sku);
        $this->assertStringEndsWith('-bundle-pr2', $deletedBundle->sku);
        $this->assertNotNull($deletedProduct->sku);

        $replacement = Products::create([
            'name' => 'Replacement Product 2',
            'sku' => 'pr2',
        ]);

        $this->assertSame('pr2', $replacement->sku);
    }

    public function test_delete_recovers_bundle_left_soft_deleted_by_a_previous_failed_attempt(): void
    {
        $product = Products::create([
            'name' => 'Partial Product',
            'sku' => 'partial-product',
        ]);

        $bundle = ProductBundle::create([
            'name' => 'Partial Bundle',
            'sku' => 'partial-bundle',
        ]);

        $bundleItem = ProductBundleItem::create([
            'product_id' => $product->id,
            'bundle_id' => $bundle->id,
            'quantity' => 1,
            'role' => 'primary',
        ]);

        $bundleItem->delete();
        $bundle->delete();

        app(ProductsController::class)->delete($product->id);

        $deletedBundle = ProductBundle::withTrashed()->findOrFail($bundle->id);

        $this->assertSoftDeleted(Products::withTrashed()->findOrFail($product->id));
        $this->assertSoftDeleted($deletedBundle);
        $this->assertStringStartsWith('deleted-', $deletedBundle->sku);
        $this->assertStringEndsWith('-partial-bundle', $deletedBundle->sku);

        $replacement = ProductBundle::create([
            'name' => 'Replacement Partial Bundle',
            'sku' => 'partial-bundle',
        ]);

        $this->assertSame('partial-bundle', $replacement->sku);
    }
}