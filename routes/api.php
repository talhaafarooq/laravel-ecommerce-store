<?php

declare(strict_types = 1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Served under "/api" with the "api" middleware group. Protected endpoints
| authenticate with a JWT bearer token issued by the auth endpoints.
|
*/

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function (): void {
    Route::middleware('throttle:api-auth')->prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/search', [ProductController::class, 'search'])->middleware('throttle:search')->name('products.search');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('products/{slug}/related', [ProductController::class, 'related'])->name('products.related');
    Route::get('products/{slug}/reviews', [ProductController::class, 'reviews'])->name('products.reviews.index');

    Route::middleware('auth:api')->group(function (): void {
        Route::prefix('auth')->name('auth.')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });

        Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('products/{slug}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::prefix('cart')->name('cart.')->group(function (): void {
            Route::get('/', [CartController::class, 'show'])->name('show');
            Route::delete('/', [CartController::class, 'clear'])->name('clear');
            Route::post('items', [CartController::class, 'store'])->name('items.store');
            Route::patch('items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('items.update');
            Route::delete('items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('items.destroy');
            Route::post('coupon', [CartController::class, 'applyCoupon'])->name('coupon.store');
            Route::delete('coupon', [CartController::class, 'removeCoupon'])->name('coupon.destroy');
        });

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:checkout')->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    });
});
