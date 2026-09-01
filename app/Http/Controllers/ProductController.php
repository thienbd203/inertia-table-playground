<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\ProductStatus;
use App\Tables\ProductsTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Musing\InertiaTable\PaginationType;

class ProductController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $paginationType = PaginationType::tryFrom(
            $request->string('pagination')->toString(),
        ) ?? PaginationType::Full;

        $modeLabels = [
            PaginationType::Full->value => 'Đầy đủ',
            PaginationType::Simple->value => 'Đơn giản',
            PaginationType::Cursor->value => 'Cursor',
        ];

        return Inertia::render('Products/Index', [
            'products' => ProductsTable::make()
                ->paginationType($paginationType)
                ->reloadProps('summary'),
            'paginationMode' => $paginationType->value,
            'paginationModes' => collect(PaginationType::cases())
                ->mapWithKeys(fn (PaginationType $type): array => [
                    $type->value => [
                        'label' => $modeLabels[$type->value],
                        'url' => route('products.index', [
                            ...$request->query(),
                            'pagination' => $type->value,
                        ]),
                    ],
                ]),
            'summary' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('status', ProductStatus::Active)->count(),
                'featured' => Product::query()->where('featured', true)->count(),
                'locked' => Product::query()->whereNotNull('locked_at')->count(),
            ],
        ]);
    }
}
