<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'email'      => fake()->safeEmail(),
            'phone'      => fake()->optional()->numerify('+1 (###) ###-####'),
            'subject'    => rtrim(fake()->sentence(5), '.'),
            'message'    => fake()->paragraph(4),
            'ip_address' => fake()->ipv4(),
            'read_at'    => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }
}
