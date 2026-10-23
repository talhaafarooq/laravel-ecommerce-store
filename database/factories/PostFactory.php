<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id'    => User::factory()->admin(),
            'title'        => rtrim(fake()->unique()->sentence(6), '.'),
            'excerpt'      => fake()->sentence(20),
            'body'         => implode("\n\n", array_map(static fn (): string => fake()->paragraph(6), range(1, 5))),
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => now()->addWeek()]);
    }
}
