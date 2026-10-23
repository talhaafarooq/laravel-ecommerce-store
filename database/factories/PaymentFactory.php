<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'  => Order::factory(),
            'gateway'   => PaymentMethod::CashOnDelivery,
            'status'    => TransactionStatus::Pending,
            'amount'    => fake()->numberBetween(10, 500) * 100,
            'currency'  => 'USD',
            'reference' => null,
            'payload'   => null,
        ];
    }

    public function stripe(string $reference): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway'   => PaymentMethod::Stripe,
            'reference' => $reference,
        ]);
    }
}
