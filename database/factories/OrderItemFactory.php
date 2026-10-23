<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(5, 200) * 100;
        $quantity  = fake()->numberBetween(1, 3);

        return [
            'order_id'     => Order::factory(),
            'product_id'   => Product::factory(),
            'product_name' => ucwords(rtrim(fake()->sentence(3), '.')),
            'product_sku'  => Str::upper(Str::random(8)),
            'unit_price'   => $unitPrice,
            'quantity'     => $quantity,
            'line_total'   => $unitPrice * $quantity,
        ];
    }

    /**
     * Snapshot the given product into the order item.
     */
    public function forProduct(Product $product, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'product_sku'  => $product->sku,
            'unit_price'   => $product->price->amount,
            'quantity'     => $quantity,
            'line_total'   => $product->price->amount * $quantity,
        ]);
    }
}
