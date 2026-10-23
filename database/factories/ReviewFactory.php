<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id'           => Product::factory(),
            'user_id'              => User::factory(),
            'rating'               => fake()->numberBetween(3, 5),
            'title'                => fake()->sentence(4),
            'body'                 => fake()->paragraph(),
            'is_approved'          => true,
            'is_verified_purchase' => false,
        ];
    }

    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => ['rating' => $rating]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['is_approved' => false]);
    }
}
