<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Models\Product;

final class ProductUnavailableException extends BusinessRuleException
{
    public static function for(?Product $product = null): self
    {
        return new self($product === null
            ? 'This product is no longer available.'
            : "\"{$product->name}\" is no longer available.");
    }

    public function errorKey(): string
    {
        return 'product_id';
    }
}
