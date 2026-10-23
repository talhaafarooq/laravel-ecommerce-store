<?php

declare(strict_types = 1);

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartCouponController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\FaqController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\Storefront\SearchSuggestionController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\StripeReturnController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('categories/{slug}', [ShopController::class, 'category'])->name('categories.show');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('search/suggestions', SearchSuggestionController::class)
    ->middleware('throttle:search')
    ->name('search.suggestions');

Route::prefix('cart')->name('cart.')->group(function (): void {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('items', [CartController::class, 'store'])->name('items.store');
    Route::patch('items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('items.update');
    Route::delete('items/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('items.destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('coupon', [CartCouponController::class, 'store'])->middleware('throttle:10,1')->name('coupon.store');
    Route::delete('coupon', [CartCouponController::class, 'destroy'])->name('coupon.destroy');
});

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('faq', FaqController::class)->name('faq');
Route::inertia('about', 'storefront/about')->name('about');
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Customers
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
    Route::get('checkout/{order}/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');
    Route::get('checkout/{order}/stripe/success', [StripeReturnController::class, 'success'])->name('checkout.stripe.success');
    Route::get('checkout/{order}/stripe/cancel', [StripeReturnController::class, 'cancel'])->name('checkout.stripe.cancel');

    Route::post('products/{slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::get('/', AccountDashboardController::class)->name('dashboard');

        Route::get('orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [AccountOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/pay', [AccountOrderController::class, 'pay'])->middleware('throttle:checkout')->name('orders.pay');
        Route::get('orders/{order}/invoice', [AccountOrderController::class, 'invoice'])->name('orders.invoice');

        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::post('addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('addresses.default');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::post('wishlist/{product}/cart', [WishlistController::class, 'moveToCart'])->name('wishlist.cart');
    });
});

require __DIR__ . '/settings.php';
