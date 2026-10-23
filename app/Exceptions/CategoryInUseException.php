<?php

declare(strict_types = 1);

namespace App\Exceptions;

final class CategoryInUseException extends BusinessRuleException
{
    public static function hasChildren(): self
    {
        return new self('This category has sub-categories. Move or delete them first.');
    }

    public static function hasProducts(): self
    {
        return new self('This category still contains products (including archived ones). Reassign them first.');
    }

    public static function invalidParent(): self
    {
        return new self('A category cannot be nested under itself or one of its sub-categories.');
    }

    public function errorKey(): string
    {
        return 'category';
    }
}
