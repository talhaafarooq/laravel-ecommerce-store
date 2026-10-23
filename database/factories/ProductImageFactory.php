<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = fake()->uuid();

        return [
            'product_id'     => Product::factory(),
            'path'           => "products/{$file}.webp",
            'thumbnail_path' => "products/thumbnails/{$file}.webp",
            'alt'            => fake()->words(3, true),
            'position'       => 0,
        ];
    }
}
