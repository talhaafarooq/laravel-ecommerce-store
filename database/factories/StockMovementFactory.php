<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);

        return [
            'product_id'    => Product::factory(),
            'type'          => StockMovementType::Restock,
            'quantity'      => $quantity,
            'balance_after' => $quantity,
            'note'          => null,
        ];
    }
}
