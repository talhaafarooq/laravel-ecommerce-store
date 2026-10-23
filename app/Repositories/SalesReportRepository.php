<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\SalesReportRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\ValueObjects\DateRange;
use Illuminate\Database\Eloquent\Builder;

final class SalesReportRepository implements SalesReportRepositoryInterface
{
    public function summary(DateRange $range): array
    {
        $totals = $this->sales($range)
            ->toBase()
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(orders.grand_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(orders.discount_total), 0) as discounts')
            ->selectRaw('COUNT(DISTINCT orders.user_id) as customers')
            ->first();

        $items = $this->sales($range)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->sum('order_items.quantity');

        return [
            'orders'    => (int) ($totals->orders ?? 0),
            'revenue'   => (int) ($totals->revenue ?? 0),
            'items'     => (int) $items,
            'discounts' => (int) ($totals->discounts ?? 0),
            'customers' => (int) ($totals->customers ?? 0),
        ];
    }

    public function dailyTotals(DateRange $range): array
    {
        $rows = $this->sales($range)
            ->toBase()
            ->selectRaw('DATE(orders.placed_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(orders.grand_total) as revenue')
            ->groupByRaw('DATE(orders.placed_at)')
            ->get()
            ->keyBy('day');

        $totals = [];

        foreach ($range->eachDay() as $day) {
            $row = $rows->get($day);

            $totals[$day] = [
                'orders'  => (int) ($row->orders ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
            ];
        }

        return $totals;
    }

    public function topProducts(DateRange $range, int $limit = 10): array
    {
        return array_values($this->sales($range)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->toBase()
            ->select('order_items.product_id')
            ->selectRaw('MAX(order_items.product_name) as name')
            ->selectRaw('MAX(order_items.product_sku) as sku')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(static fn (object $row): array => [
                'product_id' => $row->product_id === null ? null : (int) $row->product_id,
                'name'       => (string) $row->name,
                'sku'        => (string) $row->sku,
                'quantity'   => (int) $row->quantity,
                'revenue'    => (int) $row->revenue,
            ])
            ->all());
    }

    public function salesByCategory(DateRange $range): array
    {
        $category = "COALESCE(parent_categories.name, categories.name, 'Uncategorized')";

        return array_values($this->sales($range)
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories as parent_categories', 'parent_categories.id', '=', 'categories.parent_id')
            ->toBase()
            ->selectRaw("{$category} as category")
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->groupByRaw($category)
            ->orderByDesc('revenue')
            ->get()
            ->map(static fn (object $row): array => [
                'category' => (string) $row->category,
                'quantity' => (int) $row->quantity,
                'revenue'  => (int) $row->revenue,
            ])
            ->all());
    }

    public function paymentMethodBreakdown(DateRange $range): array
    {
        return array_values($this->sales($range)
            ->toBase()
            ->select('orders.payment_method as method')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(orders.grand_total) as revenue')
            ->groupBy('orders.payment_method')
            ->orderByDesc('revenue')
            ->get()
            ->map(static fn (object $row): array => [
                'method'  => (string) $row->method,
                'orders'  => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])
            ->all());
    }

    public function lifetimeRevenue(): int
    {
        return (int) Order::query()->where('payment_status', PaymentStatus::Paid)->sum('grand_total');
    }

    /**
     * Orders counted as sales within the range.
     *
     * @return Builder<Order>
     */
    private function sales(DateRange $range): Builder
    {
        return Order::query()->countedInSales()->placedBetween($range);
    }
}
