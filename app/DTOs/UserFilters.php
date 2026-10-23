<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Enums\UserRole;

final readonly class UserFilters
{
    public function __construct(
        public ?string $search = null,
        public ?UserRole $role = null,
        public ?bool $active = null,
        public int $perPage = 15,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'role'   => $this->role?->value,
            'status' => match ($this->active) {
                true    => 'active',
                false   => 'inactive',
                default => null,
            },
        ];
    }
}
