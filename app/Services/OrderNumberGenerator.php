<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;

/**
 * Generates unique, non-sequential order numbers such as "ORD-261017-K7Q2MX".
 *
 * Random suffixes avoid leaking order volume and prevent enumeration.
 */
final readonly class OrderNumberGenerator
{
    private const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private OrderRepositoryInterface $orders) {}

    public function generate(): string
    {
        do {
            $number = 'ORD-' . now()->format('ymd') . '-' . $this->randomSuffix(6);
        } while ($this->orders->findByNumber($number) !== null);

        return $number;
    }

    private function randomSuffix(int $length): string
    {
        $suffix = '';
        $max    = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $suffix .= self::ALPHABET[random_int(0, $max)];
        }

        return $suffix;
    }
}
