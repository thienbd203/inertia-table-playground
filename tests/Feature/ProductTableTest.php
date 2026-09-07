<?php

use App\Models\Category;
use App\Models\Product;
use App\ProductStatus;
use App\Tables\ProductsTable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the product table with selectable and management capabilities', function () {
    $category = Category::factory()->create(['name' => 'Âm thanh']);
    Product::factory()->for($category)->active()->unlocked()->create([
        'price' => 100_000,
        'stock' => 5,
        'rating' => 3,
        'featured' => true,
    ]);
    Product::factory()->for($category)->active()->unlocked()->create([
        'price' => 200_000,
        'stock' => 15,
        'rating' => 4,
        'featured' => false,
    ]);
    Product::factory()->for($category)->active()->locked()->create([
        'price' => 50_000,
        'stock' => 30,
        'rating' => 5,
        'featured' => true,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Products/Index')
        ->where('products.name', 'products')
        ->where('products.options.paginationType', 'cursor')
        ->where('products.capabilities.hasFilters', true)
        ->where('products.capabilities.hasBulkActions', true)
        ->where('products.capabilities.hasExports', true)
        ->where('products.capabilities.hasSummaries', true)
        ->where('products.filters.1.options', [])
        ->where('products.filters.1.remote.searchable', true)
        ->where('products.filters.1.remote.dependsOn', ['status'])
        ->where('products.filters.1.remote.withCounts', true)
        ->where('products.filters.1.remote.perPage', 3)
        ->where('products.results.currentPage', null)
        ->where('products.results.lastPage', null)
        ->where('products.results.total', null)
        ->where('products.results.selectableTotal', 2)
        ->where('products.summaries', fn (Collection $summaries): bool => $summaries->get('id') === 3
            && $summaries->get('category.name') === 1
            && $summaries->get('stock') === 50
            && $summaries->get('featured') === 2
            && (float) $summaries->get('price') === 350_000.0
            && (float) $summaries->get('rating') === 4.0)
        ->has('products.results.data', 3)
        ->has('products.views')
        ->has('products.exports', 4)
        ->where('products.exports.0.includesSummaries', true)
        ->where('products.exports.1.includesSummaries', true)
        ->where('products.exports.2.includesSummaries', false)
        ->where('products.exports.3.includesSummaries', true));
});

it('loads searchable category facets with cursor pagination and status dependency', function () {
    $audio = Category::factory()->create(['name' => 'Âm thanh']);
    $camera = Category::factory()->create(['name' => 'Máy ảnh']);
    $archived = Category::factory()->create(['name' => 'Đã lưu trữ']);
    $other = Category::factory()->create(['name' => 'Phụ kiện']);
    Product::factory()->count(2)->for($audio)->active()->create();
    Product::factory()->for($camera)->active()->create();
    Product::factory()->for($archived)->create([
        'status' => ProductStatus::Archived,
    ]);
    Product::factory()->for($other)->active()->create();
    $resource = (new ProductsTable)->resolve()->toArray();
    $categoryFilter = collect($resource['filters'])->firstWhere('attribute', 'category_id');

    $response = $this->postJson($categoryFilter['remote']['endpoint'], [
        'search' => 'm',
        'state' => [
            'filters' => [
                'status' => [
                    'enabled' => true,
                    'clause' => 'in',
                    'value' => [ProductStatus::Active->value],
                ],
            ],
        ],
    ]);

    $response
        ->assertOk()
        ->assertJsonFragment([
            'label' => 'Âm thanh',
            'count' => 2,
        ])
        ->assertJsonMissing(['label' => 'Đã lưu trữ']);
});

it('restores a selected category label even after its dependency no longer matches', function () {
    $category = Category::factory()->create(['name' => 'Chỉ có sản phẩm lưu trữ']);
    Product::factory()->for($category)->create([
        'status' => ProductStatus::Archived,
    ]);
    $request = Request::create(route('products.index'), 'GET', [
        'table' => [
            'products' => [
                'filters' => [
                    'status' => [
                        'enabled' => true,
                        'clause' => 'in',
                        'value' => [ProductStatus::Active->value],
                    ],
                    'category_id' => [
                        'enabled' => true,
                        'clause' => 'in',
                        'value' => [$category->getKey()],
                    ],
                ],
            ],
        ],
    ]);

    $resource = (new ProductsTable)->resolve($request)->toArray();
    $categoryFilter = collect($resource['filters'])->firstWhere('attribute', 'category_id');

    expect($categoryFilter['options'])->toContain([
        'value' => $category->getKey(),
        'label' => 'Chỉ có sản phẩm lưu trữ',
    ]);
});

it('applies global search across the configured product columns', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->active()->create(['name' => 'Needle Headphones']);
    Product::factory()->for($category)->active()->create(['name' => 'Desk Lamp']);

    $response = $this->get(route('products.index', [
        'table' => ['products' => ['search' => 'Needle']],
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('products.results.total', null)
        ->where('products.results.data.0.name', 'Needle Headphones')
        ->where('products.summaries.id', 1));
});

it('summarizes the complete filtered result beyond the current cursor page', function () {
    $category = Category::factory()->create();
    Product::factory()->count(12)->for($category)->active()->unlocked()->create([
        'price' => 100,
        'stock' => 2,
        'featured' => false,
    ]);

    $response = $this->get(route('products.index', [
        'table' => [
            'products' => [
                'filters' => [
                    'status' => [
                        'enabled' => true,
                        'clause' => 'in',
                        'value' => ['active'],
                    ],
                ],
            ],
        ],
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('products.results.data', 10)
        ->where('products.summaries.id', 12)
        ->where('products.summaries.stock', 24)
        ->where('products.summaries.price', fn (mixed $value): bool => (float) $value === 1_200.0));
});

it('removes the legacy pagination strategy query parameter', function () {
    $response = $this->get(route('products.index', [
        'pagination' => 'full',
        'table' => ['products' => ['search' => 'desk']],
    ]));

    $response->assertRedirect(route('products.index', [
        'table' => ['products' => ['search' => 'desk']],
    ]));
});
