<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\ProductStatus;
use App\Tables\ProductsTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->query->has('pagination')) {
            $query = $request->query();

            unset($query['pagination']);

            return redirect()->route('products.index', $query);
        }

        return Inertia::render('Products/Index', [
            'products' => ProductsTable::make()
                ->reloadProps('summary'),
            'summary' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('status', ProductStatus::Active)->count(),
                'featured' => Product::query()->where('featured', true)->count(),
                'locked' => Product::query()->whereNotNull('locked_at')->count(),
            ],
        ]);
    }
}
