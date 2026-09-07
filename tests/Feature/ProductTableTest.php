<?php

use App\Models\Category;
use App\Models\Product;
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
        ->where('products.filters.1.lazy', true)
        ->where('products.filters.1.lazyLoaded', false)
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

it('loads category options when the lazy filter is first opened', function () {
    $audio = Category::factory()->create(['name' => 'Danh mục A']);
    $camera = Category::factory()->create(['name' => 'Danh mục B']);
    Product::factory()->for($audio)->active()->create();
    Product::factory()->for($camera)->active()->create();

    $response = $this
        ->withHeader(
            'X-Musing-Inertia-Table-Lazy-Filters',
            json_encode(['products' => ['category_id']], JSON_THROW_ON_ERROR),
        )
        ->get(route('products.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('products.filters.1.lazy', true)
        ->where('products.filters.1.lazyLoaded', true)
        ->where('products.filters.1.options', [
            ['value' => $audio->getKey(), 'label' => 'Danh mục A'],
            ['value' => $camera->getKey(), 'label' => 'Danh mục B'],
        ]));
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
