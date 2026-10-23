<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic'        => fake()->randomElement(['Orders', 'Shipping', 'Payments', 'Returns']),
            'question'     => rtrim(fake()->sentence(8), '.') . '?',
            'answer'       => fake()->paragraph(),
            'position'     => fake()->numberBetween(0, 10),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
