<?php

declare(strict_types = 1);

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Loaded with the "admin." name prefix, the "/admin" URI prefix and the
| web, auth, verified and can:access-admin middleware (bootstrap/app.php).
|
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('products', ProductController::class)
    ->parameters(['products' => 'product:id'])
    ->except('show')
    ->withTrashed(['edit', 'update']);
Route::post('products/{product:id}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');

Route::scopeBindings()->group(function (): void {
    Route::post('products/{product:id}/images', [ProductImageController::class, 'store'])->withTrashed()->name('products.images.store');
    Route::post('products/{product:id}/images/{image}/primary', [ProductImageController::class, 'primary'])->withTrashed()->name('products.images.primary');
    Route::delete('products/{product:id}/images/{image}', [ProductImageController::class, 'destroy'])->withTrashed()->name('products.images.destroy');
});

Route::resource('categories', CategoryController::class)
    ->parameters(['categories' => 'category:id'])
    ->only(['index', 'store', 'update', 'destroy']);
Route::resource('brands', BrandController::class)
    ->parameters(['brands' => 'brand:id'])
    ->only(['index', 'store', 'update', 'destroy']);

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
Route::patch('users/{user}/access', [UserController::class, 'updateAccess'])->name('users.access');

Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::post('inventory/{product:id}/adjust', [InventoryController::class, 'adjust'])->withTrashed()->name('inventory.adjust');

Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

Route::resource('coupons', CouponController::class)->only(['index', 'store', 'update', 'destroy']);

Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::patch('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::resource('posts', PostController::class)
    ->parameters(['posts' => 'post:id'])
    ->except('show');
Route::resource('faqs', FaqController::class)->only(['index', 'store', 'update', 'destroy']);

Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
