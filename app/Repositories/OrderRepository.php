<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\DTOs\OrderFilters;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Concerns\BuildsLikeQueries;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class OrderRepository implements OrderRepositoryInterface
{
    use BuildsLikeQueries;

    public function paginateForCustomer(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->customerQuery($user)->paginate($perPage)->withQueryString();
    }

    public function recentForCustomer(User $user, int $limit): Collection
    {
        return $this->customerQuery($user)->limit($limit)->get();
    }

    public function customerStats(User $user): array
    {
        $orders = Order::query()->forUser($user);

        return [
            'orders' => (clone $orders)->count(),
            'open'   => (clone $orders)
                ->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped])
                ->count(),
            'spent' => (int) (clone $orders)->countedInSales()->sum('grand_total'),
        ];
    }

    public function loadDetails(Order $order): Order
    {
        return $order->load([
            'items.product:id,slug,deleted_at',
            'payments',
            'statusHistories.user:id,name',
            'user:id,name,email,phone',
        ]);
    }

    public function findByNumber(string $number): ?Order
    {
        return Order::query()->where('number', $number)->first();
    }

    public function lockForUpdate(Order $order): Order
    {
        return Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }

    public function paginateForAdmin(OrderFilters $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->with('user:id,name,email')
            ->withCount('items');

        if ($filters->search !== null) {
            $search = $filters->search;

            $query->where(function (Builder $query) use ($search): void {
                $query->where('number', 'like', $this->contains($search))
                    ->orWhere('shipping_name', 'like', $this->contains($search))
                    ->orWhere('customer_email', 'like', $this->contains($search))
                    ->orWhere('shipping_phone', 'like', $this->contains($search));
            });
        }

        $query
            ->when($filters->status, static fn (Builder $query, OrderStatus $status) => $query->where('status', $status))
            ->when($filters->paymentStatus, static fn (Builder $query, PaymentStatus $status) => $query->where('payment_status', $status))
            ->when($filters->paymentMethod, static fn (Builder $query, PaymentMethod $method) => $query->where('payment_method', $method))
            ->when($filters->from, static fn (Builder $query, CarbonInterface $from) => $query->where('placed_at', '>=', $from->startOfDay()))
            ->when($filters->to, static fn (Builder $query, CarbonInterface $to) => $query->where('placed_at', '<=', $to->endOfDay()));

        return $query
            ->latest('placed_at')
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function recent(int $limit): Collection
    {
        return Order::query()
            ->with('user:id,name,email')
            ->withCount('items')
            ->latest('placed_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function countByStatus(): array
    {
        $counts = Order::query()
            ->toBase()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = [];

        foreach (OrderStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    public function unpaidOnlineOrdersPlacedBefore(CarbonInterface $cutoff): Collection
    {
        return Order::query()
            ->where('payment_method', PaymentMethod::Stripe)
            ->where('status', OrderStatus::Pending)
            ->whereIn('payment_status', [PaymentStatus::Unpaid, PaymentStatus::Failed])
            ->where('placed_at', '<', $cutoff)
            ->orderBy('id')
            ->limit(200)
            ->get();
    }

    /**
     * @return Builder<Order>
     */
    private function customerQuery(User $user): Builder
    {
        return Order::query()
            ->forUser($user)
            ->withCount('items')
            ->with(['items' => static fn ($query) => $query->select(['id', 'order_id', 'product_name', 'product_image', 'quantity'])->limit(4)])
            ->latest('placed_at')
            ->latest('id');
    }
}
