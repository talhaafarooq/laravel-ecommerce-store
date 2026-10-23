<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'                 => Str::upper(fake()->unique()->bothify('SAVE##??')),
            'description'          => fake()->sentence(),
            'type'                 => CouponType::Percentage,
            'value'                => 10,
            'max_discount'         => null,
            'min_subtotal'         => null,
            'usage_limit'          => null,
            'usage_limit_per_user' => null,
            'times_used'           => 0,
            'starts_at'            => null,
            'expires_at'           => null,
            'is_active'            => true,
        ];
    }

    public function percentage(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => CouponType::Percentage,
            'value' => $percent,
        ]);
    }

    /**
     * A fixed discount in minor units.
     */
    public function fixed(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type'  => CouponType::Fixed,
            'value' => $amount,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
