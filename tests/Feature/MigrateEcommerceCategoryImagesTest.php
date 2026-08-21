<?php

namespace Tests\Feature;

use App\Models\EcommerceProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrateEcommerceCategoryImagesTest extends TestCase
{
    private string $relativePath = 'ecommerce-products/category-migration-command-test.png';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ecommerce_product_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        File::delete([
            $this->sourcePath(),
            $this->destinationPath(),
        ]);
    }

    protected function tearDown(): void
    {
        File::delete([
            $this->sourcePath(),
            $this->destinationPath(),
        ]);

        Schema::dropIfExists('ecommerce_product_categories');

        parent::tearDown();
    }

    public function test_command_copies_legacy_category_image_to_public_uploads(): void
    {
        EcommerceProductCategory::create([
            'name' => 'Cup',
            'slug' => 'cup',
            'image' => $this->relativePath,
        ]);

        File::ensureDirectoryExists(dirname($this->sourcePath()));
        File::put($this->sourcePath(), 'category-image');

        $this->artisan('ecommerce:migrate-category-images')
            ->expectsOutputToContain('1 copied')
            ->assertSuccessful();

        $this->assertFileExists($this->destinationPath());
        $this->assertSame('category-image', File::get($this->destinationPath()));
    }

    private function sourcePath(): string
    {
        return storage_path('app/public/' . $this->relativePath);
    }

    private function destinationPath(): string
    {
        return public_path('uploads/' . $this->relativePath);
    }
}
