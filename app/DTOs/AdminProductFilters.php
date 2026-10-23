<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Enums\StockStatus;

final readonly class AdminProductFilters
{
    public function __construct(
        public ?string $search = null,
        public ?int $categoryId = null,
        public ?bool $active = null,
        public ?StockStatus $stock = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public bool $withTrashed = false,
        public int $perPage = 15,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search'   => $this->search,
            'category' => $this->categoryId,
            'status'   => match ($this->active) {
                true    => 'active',
                false   => 'inactive',
                default => null,
            },
            'stock'     => $this->stock?->value,
            'sort'      => $this->sort,
            'direction' => $this->direction,
            'archived'  => $this->withTrashed,
        ];
    }
}
