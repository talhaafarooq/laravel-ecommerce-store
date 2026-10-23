<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Concerns\BuildsLikeQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ReviewRepository implements ReviewRepositoryInterface
{
    use BuildsLikeQueries;

    public function paginateApprovedForProduct(Product $product, int $perPage = 6): LengthAwarePaginator
    {
        return $product->reviews()
            ->approved()
            ->with('user:id,name,avatar_path')
            ->latest()
            ->latest('id')
            ->paginate($perPage, pageName: 'reviews_page')
            ->withQueryString();
    }

    public function ratingBreakdown(Product $product): array
    {
        $counts = $product->reviews()
            ->approved()
            ->toBase()
            ->select('rating')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $breakdown = [];

        foreach ([5, 4, 3, 2, 1] as $rating) {
            $breakdown[$rating] = (int) ($counts[$rating] ?? 0);
        }

        return $breakdown;
    }

    public function approvedAggregate(Product $product): array
    {
        $reviews = $product->reviews()->approved();

        return [
            'average' => round((float) (clone $reviews)->avg('rating'), 2),
            'count'   => (clone $reviews)->count(),
        ];
    }

    public function findForUser(User $user, Product $product): ?Review
    {
        return Review::query()
            ->where('user_id', $user->getKey())
            ->where('product_id', $product->getKey())
            ->first();
    }

    public function hasPurchased(User $user, Product $product): bool
    {
        return OrderItem::query()
            ->where('product_id', $product->getKey())
            ->whereHas('order', static function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey())->where('status', OrderStatus::Delivered);
            })
            ->exists();
    }

    public function paginateForAdmin(?string $search = null, ?bool $approved = null, int $perPage = 15): LengthAwarePaginator
    {
        return Review::query()
            ->with(['user:id,name,email', 'product:id,name,slug,deleted_at'])
            ->when($search !== null, function (Builder $query) use ($search): void {
                $like = $this->contains((string) $search);

                $query->where(static function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhereHas('product', static fn (Builder $product) => $product->where('name', 'like', $like));
                });
            })
            ->when($approved !== null, static fn (Builder $query) => $query->where('is_approved', $approved))
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function latest(int $limit): Collection
    {
        return Review::query()
            ->with(['user:id,name', 'product:id,name,slug,deleted_at'])
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function countPending(): int
    {
        return Review::query()->where('is_approved', false)->count();
    }
}
