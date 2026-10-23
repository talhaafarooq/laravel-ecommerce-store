<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Contracts\Repositories\SalesReportRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\OrderStatus;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\ValueObjects\DateRange;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;

/**
 * Assembles the figures shown on the admin dashboard.
 */
final readonly class DashboardService
{
    public function __construct(
        private SalesReportRepositoryInterface $sales,
        private OrderRepositoryInterface $orders,
        private ProductRepositoryInterface $products,
        private UserRepositoryInterface $users,
        private ReviewRepositoryInterface $reviews,
    ) {}

    /**
     * @return array{
     *     stats: array<string, mixed>,
     *     sales_chart: list<array{date: string, revenue: float, orders: int}>,
     *     status_breakdown: list<array{status: string, label: string, tone: string, count: int}>,
     *     top_products: list<array{product_id: int|null, name: string, sku: string, quantity: int, revenue: int}>,
     *     recent_orders: Collection<int, Order>,
     *     low_stock: Collection<int, Product>,
     *     latest_reviews: Collection<int, Review>,
     * }
     */
    public function overview(): array
    {
        $range    = DateRange::lastDays(30);
        $current  = $this->sales->summary($range);
        $previous = $this->sales->summary($range->previous());
        $counts   = $this->orders->countByStatus();

        return [
            'stats' => [
                'revenue'             => Money::of($current['revenue']),
                'revenue_change'      => $this->change($current['revenue'], $previous['revenue']),
                'orders'              => $current['orders'],
                'orders_change'       => $this->change($current['orders'], $previous['orders']),
                'average_order_value' => Money::of($current['orders'] > 0 ? intdiv($current['revenue'], $current['orders']) : 0),
                'customers'           => $this->users->countCustomers(),
                'new_customers'       => $this->users->countCustomersSince($range->from),
                'lifetime_revenue'    => Money::of($this->sales->lifetimeRevenue()),
                'active_products'     => $this->products->countActive(),
                'low_stock'           => $this->products->countLowStock(),
                'pending_orders'      => $counts[OrderStatus::Pending->value] ?? 0,
                'pending_reviews'     => $this->reviews->countPending(),
                'unread_messages'     => ContactMessage::query()->unread()->count(),
            ],
            'sales_chart'      => $this->chart($this->sales->dailyTotals($range)),
            'status_breakdown' => array_map(static fn (OrderStatus $status): array => [
                'status' => $status->value,
                'label'  => $status->label(),
                'tone'   => $status->tone(),
                'count'  => $counts[$status->value] ?? 0,
            ], OrderStatus::cases()),
            'top_products'   => $this->sales->topProducts($range, 5),
            'recent_orders'  => $this->orders->recent(8),
            'low_stock'      => $this->products->lowStock(6),
            'latest_reviews' => $this->reviews->latest(5),
        ];
    }

    /**
     * @param array<string, array{orders: int, revenue: int}> $daily
     *
     * @return list<array{date: string, revenue: float, orders: int}>
     */
    private function chart(array $daily): array
    {
        $points = [];

        foreach ($daily as $date => $totals) {
            $points[] = ['date' => $date, 'revenue' => $totals['revenue'] / 100, 'orders' => $totals['orders']];
        }

        return $points;
    }

    /**
     * Percentage change from the previous period, or null when there is no baseline.
     */
    private function change(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
