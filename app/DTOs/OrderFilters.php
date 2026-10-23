<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final readonly class OrderFilters
{
    public function __construct(
        public ?string $search = null,
        public ?OrderStatus $status = null,
        public ?PaymentStatus $paymentStatus = null,
        public ?PaymentMethod $paymentMethod = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public int $perPage = 15,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search'         => $this->search,
            'status'         => $this->status?->value,
            'payment_status' => $this->paymentStatus?->value,
            'payment_method' => $this->paymentMethod?->value,
            'from'           => $this->from?->toDateString(),
            'to'             => $this->to?->toDateString(),
        ];
    }
}
