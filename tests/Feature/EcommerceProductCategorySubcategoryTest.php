<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EcommerceProductCategoryController;
use App\Http\Requests\EcommerceProductCategoryStoreRequest;
use App\Http\Requests\EcommerceProductCategoryUpdateRequest;
use App\Models\EcommerceProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EcommerceProductCategorySubcategoryTest extends TestCase
{
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ecommerce_product_categories');

        parent::tearDown();
    }

    public function test_store_creates_a_main_category_with_its_new_and_existing_subcategories(): void
    {
        $existing = EcommerceProductCategory::create([
            'name' => 'Kategori Lepas',
            'slug' => 'kategori-lepas',
        ]);

        $request = $this->formRequest(EcommerceProductCategoryStoreRequest::class, '/erp/ecommerce-product-categories', 'POST', [
            'name' => 'Minuman',
            'slug' => '',
            'existing_child_ids' => [$existing->id],
            'existing_children' => [$existing->id => ['name' => 'Kategori Dipindah']],
            'subcategories' => [
                ['name' => 'Kopi', 'description' => 'Semua kopi'],
                ['name' => '', 'description' => ''],
            ],
        ]);

        app(EcommerceProductCategoryController::class)->store($request);

        $main = EcommerceProductCategory::where('name', 'Minuman')->firstOrFail();

        $this->assertNull($main->parent_id);
        $this->assertSame('minuman', $main->slug);

        $children = $main->children()->orderBy('name')->get();

        $this->assertSame(['Kategori Dipindah', 'Kopi'], $children->pluck('name')->all());
        $this->assertSame($main->id, $existing->fresh()->parent_id);
        $this->assertSame('kopi', $children->firstWhere('name', 'Kopi')->slug);
    }

    public function test_update_keeps_renamed_children_and_releases_the_removed_one_as_a_main_category(): void
    {
        $main = EcommerceProductCategory::create(['name' => 'Minuman', 'slug' => 'minuman']);
        $kept = EcommerceProductCategory::create(['name' => 'Kopi', 'slug' => 'kopi', 'parent_id' => $main->id]);
        $released = EcommerceProductCategory::create(['name' => 'Teh', 'slug' => 'teh', 'parent_id' => $main->id]);

        $request = $this->formRequest(
            EcommerceProductCategoryUpdateRequest::class,
            '/erp/ecommerce-product-categories/' . $main->id,
            'PUT',
            [
                'name' => 'Minuman',
                'slug' => 'minuman',
                'existing_child_ids' => [$kept->id],
                'existing_children' => [$kept->id => ['name' => 'Kopi Susu']],
                'subcategories' => [['name' => 'Jus']],
            ],
            ['category' => $main]
        );

        app(EcommerceProductCategoryController::class)->update($request, $main);

        $this->assertSame('Kopi Susu', $kept->fresh()->name);
        $this->assertNull($released->fresh()->parent_id);
        $this->assertSame(
            ['Jus', 'Kopi Susu'],
            $main->children()->orderBy('name')->pluck('name')->all()
        );
    }

    public function test_update_ignores_a_child_that_would_create_a_cycle(): void
    {
        $main = EcommerceProductCategory::create(['name' => 'Minuman', 'slug' => 'minuman']);
        $child = EcommerceProductCategory::create(['name' => 'Kopi', 'slug' => 'kopi', 'parent_id' => $main->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->formRequest(
            EcommerceProductCategoryUpdateRequest::class,
            '/erp/ecommerce-product-categories/' . $child->id,
            'PUT',
            [
                'name' => 'Kopi',
                'slug' => 'kopi',
                'existing_child_ids' => [$child->id, $main->id],
            ],
            ['category' => $child]
        );
    }

    public function test_new_subcategory_with_a_taken_slug_gets_a_unique_one(): void
    {
        EcommerceProductCategory::create(['name' => 'Kopi', 'slug' => 'kopi']);

        $request = $this->formRequest(EcommerceProductCategoryStoreRequest::class, '/erp/ecommerce-product-categories', 'POST', [
            'name' => 'Minuman',
            'subcategories' => [['name' => 'Kopi']],
        ]);

        app(EcommerceProductCategoryController::class)->store($request);

        $main = EcommerceProductCategory::where('name', 'Minuman')->firstOrFail();

        $this->assertSame('kopi-2', $main->children()->firstOrFail()->slug);
    }

    public function test_data_endpoint_separates_main_and_sub_categories_per_tab(): void
    {
        $main = EcommerceProductCategory::create(['name' => 'Minuman', 'slug' => 'minuman']);
        EcommerceProductCategory::create(['name' => 'Kopi', 'slug' => 'kopi', 'parent_id' => $main->id]);
        EcommerceProductCategory::create(['name' => 'Makanan', 'slug' => 'makanan']);

        $controller = app(EcommerceProductCategoryController::class);

        $mainRows = $this->dataRows($controller, ['scope' => 'root']);
        $subRows = $this->dataRows($controller, ['scope' => 'sub']);
        $filteredSubRows = $this->dataRows($controller, ['scope' => 'sub', 'parent_id' => $main->id]);

        $this->assertSame(['Makanan', 'Minuman'], array_column($mainRows, 'name'));
        $this->assertSame(['Kopi'], array_column($subRows, 'name'));
        $this->assertSame(['Kopi'], array_column($filteredSubRows, 'name'));

        $minuman = collect($mainRows)->firstWhere('name', 'Minuman');
        $this->assertStringContainsString('1 sub', $minuman['subcategories']);
        $this->assertStringContainsString('Minuman', $subRows[0]['parent']);
    }

    private function dataRows(EcommerceProductCategoryController $controller, array $query): array
    {
        $response = $controller->data(\Illuminate\Http\Request::create('/erp/ecommerce-product-categories/data', 'GET', $query));

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param  class-string<FormRequest>  $class
     */
    private function formRequest(string $class, string $uri, string $method, array $data, array $routeParameters = []): FormRequest
    {
        /** @var FormRequest $request */
        $request = $class::create($uri, $method, $data);

        $request->setContainer(app())->setRedirector(app('redirect'));

        if ($routeParameters) {
            $route = new \Illuminate\Routing\Route([$method], $uri, []);
            $route->bind($request);

            foreach ($routeParameters as $key => $value) {
                $route->setParameter($key, $value);
            }

            $request->setRouteResolver(fn () => $route);
        }

        $request->validateResolved();

        return $request;
    }
}
