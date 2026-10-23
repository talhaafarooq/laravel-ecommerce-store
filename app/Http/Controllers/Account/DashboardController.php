<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Account;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\WishlistService;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, OrderRepositoryInterface $orders, WishlistService $wishlist): Response
    {
        $user  = $request->user();
        $stats = $orders->customerStats($user);

        return Inertia::render('account/dashboard', [
            'stats' => [
                'orders'   => $stats['orders'],
                'open'     => $stats['open'],
                'spent'    => Money::of($stats['spent']),
                'wishlist' => count($wishlist->productIds($user)),
            ],
            'recentOrders' => OrderResource::collection($orders->recentForCustomer($user, 5))->resolve(),
        ]);
    }
}
