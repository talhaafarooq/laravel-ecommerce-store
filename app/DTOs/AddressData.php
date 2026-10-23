<?php

declare(strict_types = 1);

namespace App\DTOs;

use App\Models\Address;

final readonly class AddressData
{
    public function __construct(
        public string $fullName,
        public string $phone,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $state,
        public string $postalCode,
        public string $country,
        public ?string $label = null,
    ) {}

    /**
     * @param array<string, mixed> $data Validated, snake_case address fields.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: (string) $data['full_name'],
            phone: (string) $data['phone'],
            line1: (string) $data['line1'],
            line2: isset($data['line2']) ? (string) $data['line2'] : null,
            city: (string) $data['city'],
            state: isset($data['state']) ? (string) $data['state'] : null,
            postalCode: (string) $data['postal_code'],
            country: strtoupper((string) $data['country']),
            label: isset($data['label']) ? (string) $data['label'] : null,
        );
    }

    public static function fromModel(Address $address): self
    {
        return new self(
            fullName: $address->full_name,
            phone: $address->phone,
            line1: $address->line1,
            line2: $address->line2,
            city: $address->city,
            state: $address->state,
            postalCode: $address->postal_code,
            country: $address->country,
            label: $address->label,
        );
    }

    /**
     * Attributes for persisting an {@see Address}.
     *
     * @return array<string, string|null>
     */
    public function toAttributes(): array
    {
        return [
            'label'       => $this->label,
            'full_name'   => $this->fullName,
            'phone'       => $this->phone,
            'line1'       => $this->line1,
            'line2'       => $this->line2,
            'city'        => $this->city,
            'state'       => $this->state,
            'postal_code' => $this->postalCode,
            'country'     => $this->country,
        ];
    }

    /**
     * Attributes for the shipping snapshot stored on an order.
     *
     * @return array<string, string|null>
     */
    public function toOrderAttributes(): array
    {
        return [
            'shipping_name'        => $this->fullName,
            'shipping_phone'       => $this->phone,
            'shipping_line1'       => $this->line1,
            'shipping_line2'       => $this->line2,
            'shipping_city'        => $this->city,
            'shipping_state'       => $this->state,
            'shipping_postal_code' => $this->postalCode,
            'shipping_country'     => $this->country,
        ];
    }
}
