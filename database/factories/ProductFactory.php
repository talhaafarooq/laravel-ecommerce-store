<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucwords(rtrim(fake()->unique()->sentence(3), '.'));

        return [
            'category_id'         => Category::factory(),
            'brand_id'            => null,
            'name'                => $name,
            'sku'                 => Str::upper(Str::random(3)) . '-' . fake()->unique()->numerify('######'),
            'short_description'   => fake()->sentence(14),
            'description'         => fake()->paragraphs(2, true),
            'price'               => fake()->numberBetween(5, 500) * 100 + 99,
            'compare_at_price'    => null,
            'stock_quantity'      => fake()->numberBetween(20, 120),
            'low_stock_threshold' => 5,
            'is_active'           => true,
            'is_featured'         => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => 0]);
    }

    public function withStock(int $quantity): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => $quantity]);
    }

    /**
     * Price the product in minor units (e.g. 1999 for 19.99).
     */
    public function priced(int $amount, ?int $compareAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'price'            => $amount,
            'compare_at_price' => $compareAt,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = is_int($attributes['price']) ? $attributes['price'] : 1999;

            return ['compare_at_price' => (int) round($price * 1.25)];
        });
    }
}
