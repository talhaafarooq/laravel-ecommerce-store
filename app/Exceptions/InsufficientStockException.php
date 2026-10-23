<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Models\Product;

final class InsufficientStockException extends BusinessRuleException
{
    public static function for(Product $product, int $requested): self
    {
        $available = max($product->stock_quantity, 0);

        $message = $available === 0
            ? "\"{$product->name}\" is out of stock."
            : "Only {$available} unit(s) of \"{$product->name}\" are available (you requested {$requested}).";

        return new self($message);
    }

    public static function perItemLimit(Product $product, int $limit): self
    {
        return new self("You can add up to {$limit} unit(s) of \"{$product->name}\" per order.");
    }

    public function errorKey(): string
    {
        return 'quantity';
    }

    public function status(): int
    {
        return 409;
    }
}
