<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\SalesReportRepositoryInterface;
use App\Enums\PaymentMethod;
use App\ValueObjects\DateRange;
use App\ValueObjects\Money;
use Generator;

final readonly class SalesReportService
{
    public function __construct(private SalesReportRepositoryInterface $sales) {}

    /**
     * @return array<string, mixed>
     */
    public function build(DateRange $range): array
    {
        $summary  = $this->sales->summary($range);
        $previous = $this->sales->summary($range->previous());

        return [
            'range'   => $range->toArray(),
            'summary' => [
                'orders'              => $summary['orders'],
                'revenue'             => Money::of($summary['revenue']),
                'items'               => $summary['items'],
                'discounts'           => Money::of($summary['discounts']),
                'customers'           => $summary['customers'],
                'average_order_value' => Money::of($summary['orders'] > 0 ? intdiv($summary['revenue'], $summary['orders']) : 0),
                'revenue_change'      => $previous['revenue'] > 0 ? round(($summary['revenue'] - $previous['revenue']) / $previous['revenue'] * 100, 1) : null,
                'orders_change'       => $previous['orders']  > 0 ? round(($summary['orders'] - $previous['orders']) / $previous['orders'] * 100, 1) : null,
            ],
            'daily' => array_map(
                static fn (string $date, array $totals): array => [
                    'date'    => $date,
                    'orders'  => $totals['orders'],
                    'revenue' => $totals['revenue'] / 100,
                ],
                array_keys($daily = $this->sales->dailyTotals($range)),
                array_values($daily),
            ),
            'top_products' => array_map(
                static fn (array $row): array => [...$row, 'revenue' => Money::of($row['revenue'])],
                $this->sales->topProducts($range, 10),
            ),
            'categories' => array_map(
                static fn (array $row): array => [...$row, 'revenue' => Money::of($row['revenue'])],
                $this->sales->salesByCategory($range),
            ),
            'payment_methods' => array_map(
                static fn (array $row): array => [
                    'method'  => PaymentMethod::tryFrom($row['method'])?->label() ?? $row['method'],
                    'orders'  => $row['orders'],
                    'revenue' => Money::of($row['revenue']),
                ],
                $this->sales->paymentMethodBreakdown($range),
            ),
        ];
    }

    /**
     * Rows for the CSV export, starting with the header.
     *
     * @return Generator<int, list<string|int>>
     */
    public function csvRows(DateRange $range): Generator
    {
        yield ['Date', 'Orders', 'Revenue'];

        foreach ($this->sales->dailyTotals($range) as $date => $totals) {
            yield [$date, $totals['orders'], Money::of($totals['revenue'])->toDecimal()];
        }

        yield [];
        yield ['Top products', 'Units sold', 'Revenue'];

        foreach ($this->sales->topProducts($range, 25) as $product) {
            yield ["{$product['name']} ({$product['sku']})", $product['quantity'], Money::of($product['revenue'])->toDecimal()];
        }
    }
}
