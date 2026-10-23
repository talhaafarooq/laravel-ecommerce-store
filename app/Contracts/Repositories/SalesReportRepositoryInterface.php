<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\ValueObjects\DateRange;

/**
 * Aggregated sales figures. Monetary values are returned in minor units.
 */
interface SalesReportRepositoryInterface
{
    /**
     * @return array{orders: int, revenue: int, items: int, discounts: int, customers: int}
     */
    public function summary(DateRange $range): array;

    /**
     * Orders and revenue per day, keyed by Y-m-d.
     *
     * @return array<string, array{orders: int, revenue: int}>
     */
    public function dailyTotals(DateRange $range): array;

    /**
     * @return list<array{product_id: int|null, name: string, sku: string, quantity: int, revenue: int}>
     */
    public function topProducts(DateRange $range, int $limit = 10): array;

    /**
     * Revenue grouped by top-level category.
     *
     * @return list<array{category: string, quantity: int, revenue: int}>
     */
    public function salesByCategory(DateRange $range): array;

    /**
     * @return list<array{method: string, orders: int, revenue: int}>
     */
    public function paymentMethodBreakdown(DateRange $range): array;

    /**
     * Total revenue of all paid orders.
     */
    public function lifetimeRevenue(): int;
}
