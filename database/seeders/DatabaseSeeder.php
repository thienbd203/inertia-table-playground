<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\ProductStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        collect([
            'Âm thanh',
            'Bàn làm việc',
            'Gia dụng',
            'Máy ảnh',
            'Phụ kiện',
            'Thiết bị di động',
        ])->each(function (string $name): void {
            $category = Category::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);

            Product::factory()
                ->count(30)
                ->for($category)
                ->sequence(fn (Sequence $sequence): array => [
                    'status' => match ($sequence->index % 6) {
                        0 => ProductStatus::Draft,
                        5 => ProductStatus::Archived,
                        default => ProductStatus::Active,
                    },
                    'featured' => $sequence->index % 4 === 0,
                    'stock' => ($sequence->index * 17) % 251,
                    'locked_at' => $sequence->index % 13 === 0
                        ? now()->subDays($sequence->index + 1)
                        : null,
                ])
                ->create();
        });
    }
}
