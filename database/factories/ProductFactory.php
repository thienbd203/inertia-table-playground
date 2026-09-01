<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => str(is_array($name) ? implode(' ', $name) : $name)->headline(),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'description' => fake()->sentence(12),
            'status' => fake()->randomElement(ProductStatus::cases()),
            'price' => fake()->randomFloat(2, 49_000, 4_990_000),
            'stock' => fake()->numberBetween(0, 250),
            'rating' => fake()->randomFloat(1, 1, 5),
            'featured' => fake()->boolean(25),
            'launched_at' => fake()->dateTimeBetween('-3 years'),
            'locked_at' => fake()->boolean(8) ? fake()->dateTimeBetween('-30 days') : null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => ['locked_at' => now()]);
    }

    public function unlocked(): static
    {
        return $this->state(fn (): array => ['locked_at' => null]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Active]);
    }
}
