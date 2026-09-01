<?php

namespace App\Models;

use App\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ProductStatus $status
 * @property bool $featured
 * @property Carbon|null $locked_at
 */
#[Fillable([
    'category_id',
    'name',
    'sku',
    'description',
    'status',
    'price',
    'stock',
    'rating',
    'featured',
    'launched_at',
    'locked_at',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'price' => 'decimal:2',
            'stock' => 'integer',
            'rating' => 'decimal:1',
            'featured' => 'boolean',
            'launched_at' => 'date',
            'locked_at' => 'datetime',
        ];
    }
}
