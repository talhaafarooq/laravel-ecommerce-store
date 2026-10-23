<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Models\Product;

final class InventoryException extends BusinessRuleException
{
    public static function wouldBeNegative(Product $product, int $change): self
    {
        return new self("Cannot remove {$change} unit(s) from \"{$product->name}\"; only {$product->stock_quantity} in stock.");
    }

    public function errorKey(): string
    {
        return 'quantity';
    }
}
