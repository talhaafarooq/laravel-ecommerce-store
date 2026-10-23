<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;

/**
 * Remembers the products a shopper viewed during their session.
 */
final readonly class RecentlyViewedService
{
    private const string SESSION_KEY = 'recently_viewed';

    public function __construct(private ProductRepositoryInterface $products) {}

    public function record(Session $session, Product $product): void
    {
        $ids = array_values(array_diff($this->ids($session), [$product->id]));

        array_unshift($ids, $product->id);

        $session->put(self::SESSION_KEY, array_slice($ids, 0, $this->limit() + 1));
    }

    /**
     * @return Collection<int, Product>
     */
    public function get(Session $session, ?Product $except = null): Collection
    {
        $ids = array_values(array_diff($this->ids($session), $except === null ? [] : [$except->id]));

        return $this->products->findVisibleByIds(array_slice($ids, 0, $this->limit()));
    }

    /**
     * @return list<int>
     */
    private function ids(Session $session): array
    {
        $ids = $session->get(self::SESSION_KEY, []);

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    private function limit(): int
    {
        return (int) config('shop.catalog.recently_viewed_limit', 8);
    }
}
