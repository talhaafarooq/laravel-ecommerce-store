<?php

declare(strict_types = 1);

namespace App\Http\Middleware;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CartService;
use App\Services\CartSession;
use App\Services\CatalogService;
use App\Services\OrderTotalsCalculator;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'master';

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly CartSession $cartSession,
        private readonly CartService $carts,
        private readonly WishlistService $wishlist,
        private readonly OrderTotalsCalculator $totals,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Everything except the authenticated user is resolved lazily so that
     * partial reloads only pay for the props they ask for.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user instanceof User ? UserResource::make($user)->resolve($request) : null,
            ],
            'store'       => Inertia::once(fn (): array => $this->storeDetails()),
            'navigation'  => Inertia::once(fn (): array => CategoryResource::collection($this->catalog->navigation())->resolve($request)),
            'cartCount'   => fn (): int => $this->carts->itemCount($this->cartSession->current($request)),
            'wishlistIds' => fn (): array => $user instanceof User ? $this->wishlist->productIds($user) : [],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storeDetails(): array
    {
        return [
            'currency'                => config('shop.currency.code'),
            'support_email'           => config('shop.contact.support_email'),
            'support_phone'           => config('shop.contact.support_phone'),
            'address'                 => config('shop.contact.address'),
            'social'                  => config('shop.social'),
            'free_shipping_threshold' => $this->totals->freeShippingThreshold(),
        ];
    }
}
