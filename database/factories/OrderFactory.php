<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(20, 400) * 100;
        $shipping = 500;

        return [
            'number'               => 'ORD-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
            'user_id'              => User::factory(),
            'status'               => OrderStatus::Pending,
            'payment_status'       => PaymentStatus::Unpaid,
            'payment_method'       => PaymentMethod::CashOnDelivery,
            'currency'             => 'USD',
            'subtotal'             => $subtotal,
            'discount_total'       => 0,
            'shipping_total'       => $shipping,
            'tax_total'            => 0,
            'grand_total'          => $subtotal + $shipping,
            'customer_email'       => fake()->safeEmail(),
            'shipping_name'        => fake()->name(),
            'shipping_phone'       => fake()->numerify('+1 (###) ###-####'),
            'shipping_line1'       => fake()->streetAddress(),
            'shipping_line2'       => null,
            'shipping_city'        => fake()->city(),
            'shipping_state'       => fake()->randomElement(['CA', 'NY', 'TX', 'WA', 'IL', 'FL', 'MA', 'CO']),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country'     => 'US',
            'placed_at'            => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'paid_at'        => now(),
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => ['payment_method' => PaymentMethod::Stripe]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => OrderStatus::Delivered,
            'payment_status' => PaymentStatus::Paid,
            'paid_at'        => now(),
            'shipped_at'     => now(),
            'delivered_at'   => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
