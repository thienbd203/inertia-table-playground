<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the product table with selectable and management capabilities', function () {
    $category = Category::factory()->create(['name' => 'Âm thanh']);
    Product::factory()->count(2)->for($category)->active()->unlocked()->create();
    Product::factory()->for($category)->active()->locked()->create();

    $response = $this->get(route('products.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Products/Index')
        ->where('products.name', 'products')
        ->where('products.options.paginationType', 'cursor')
        ->where('products.capabilities.hasFilters', true)
        ->where('products.capabilities.hasBulkActions', true)
        ->where('products.capabilities.hasExports', true)
        ->where('products.results.currentPage', null)
        ->where('products.results.lastPage', null)
        ->where('products.results.total', null)
        ->where('products.results.selectableTotal', 2)
        ->has('products.results.data', 3)
        ->has('products.views')
        ->has('products.exports', 4));
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
        ->where('products.results.data.0.name', 'Needle Headphones'));
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
