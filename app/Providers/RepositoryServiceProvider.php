<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\CouponRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\PostRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Contracts\Repositories\SalesReportRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\CartRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PostRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\SalesReportRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds repository contracts to their Eloquent implementations.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $singletons = [
        CartRepositoryInterface::class        => CartRepository::class,
        CategoryRepositoryInterface::class    => CategoryRepository::class,
        CouponRepositoryInterface::class      => CouponRepository::class,
        OrderRepositoryInterface::class       => OrderRepository::class,
        PostRepositoryInterface::class        => PostRepository::class,
        ProductRepositoryInterface::class     => ProductRepository::class,
        ReviewRepositoryInterface::class      => ReviewRepository::class,
        SalesReportRepositoryInterface::class => SalesReportRepository::class,
        UserRepositoryInterface::class        => UserRepository::class,
    ];
}
