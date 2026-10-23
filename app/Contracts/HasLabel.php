<?php

declare(strict_types = 1);

namespace App\Contracts;

interface HasLabel
{
    /**
     * Get the human readable label.
     */
    public function label(): string;
}
