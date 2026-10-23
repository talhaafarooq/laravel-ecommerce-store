<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'label'       => fake()->randomElement(['Home', 'Office', null]),
            'full_name'   => fake()->name(),
            'phone'       => fake()->numerify('+1 (###) ###-####'),
            'line1'       => fake()->streetAddress(),
            'line2'       => fake()->optional()->numerify('Apt ###'),
            'city'        => fake()->city(),
            'state'       => fake()->randomElement(['CA', 'NY', 'TX', 'WA', 'IL', 'FL', 'MA', 'CO']),
            'postal_code' => fake()->postcode(),
            'country'     => 'US',
            'is_default'  => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
