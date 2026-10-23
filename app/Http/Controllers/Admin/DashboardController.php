<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReviewResource;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): Response
    {
        $overview = $dashboard->overview();

        return Inertia::render('admin/dashboard', [
            'stats'           => $overview['stats'],
            'salesChart'      => $overview['sales_chart'],
            'statusBreakdown' => $overview['status_breakdown'],
            'topProducts'     => $overview['top_products'],
            'recentOrders'    => OrderResource::collection($overview['recent_orders'])->resolve(),
            'lowStock'        => AdminProductResource::collection($overview['low_stock'])->resolve(),
            'latestReviews'   => ReviewResource::collection($overview['latest_reviews'])->resolve(),
        ]);
    }
}
