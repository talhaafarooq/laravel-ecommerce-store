<?php

declare(strict_types = 1);

use App\Enums\OrderStatus;
use App\Enums\StockStatus;

it('only allows moving an order forward', function (OrderStatus $from, OrderStatus $to, bool $allowed): void {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'pending to processing'  => [OrderStatus::Pending, OrderStatus::Processing, true],
    'pending to cancelled'   => [OrderStatus::Pending, OrderStatus::Cancelled, true],
    'processing to shipped'  => [OrderStatus::Processing, OrderStatus::Shipped, true],
    'shipped to delivered'   => [OrderStatus::Shipped, OrderStatus::Delivered, true],
    'pending to delivered'   => [OrderStatus::Pending, OrderStatus::Delivered, false],
    'shipped to processing'  => [OrderStatus::Shipped, OrderStatus::Processing, false],
    'shipped to cancelled'   => [OrderStatus::Shipped, OrderStatus::Cancelled, false],
    'delivered to cancelled' => [OrderStatus::Delivered, OrderStatus::Cancelled, false],
    'cancelled to pending'   => [OrderStatus::Cancelled, OrderStatus::Pending, false],
]);

it('knows which statuses are final', function (): void {
    expect(OrderStatus::Delivered->isFinal())->toBeTrue()
        ->and(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Pending->isFinal())->toBeFalse();
});

it('derives the stock status from quantity and threshold', function (): void {
    expect(StockStatus::for(0, 5))->toBe(StockStatus::OutOfStock)
        ->and(StockStatus::for(3, 5))->toBe(StockStatus::LowStock)
        ->and(StockStatus::for(5, 5))->toBe(StockStatus::LowStock)
        ->and(StockStatus::for(6, 5))->toBe(StockStatus::InStock);
});
