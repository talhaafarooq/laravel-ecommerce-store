<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockMovementType;
use App\Events\ProductStockLow;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryException;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Owns every change to product stock levels and records it in the ledger.
 */
final readonly class InventoryService
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private Dispatcher $events,
    ) {}

    /**
     * Take stock for a sold order line. The product must already be locked.
     *
     * @throws InsufficientStockException
     */
    public function recordSale(Product $product, int $quantity, Order $order): void
    {
        if ($quantity > $product->stock_quantity) {
            throw InsufficientStockException::for($product, $quantity);
        }

        $before = $product->stock_quantity;

        $product->stock_quantity -= $quantity;
        $product->sales_count += $quantity;
        $product->save();

        $this->log($product, StockMovementType::Sale, -$quantity, order: $order);
        $this->notifyIfLow($product, $before);
    }

    /**
     * Put the items of a cancelled order back into stock.
     *
     * Must be called inside a database transaction.
     */
    public function restockOrder(Order $order, ?User $actor = null): void
    {
        $order->loadMissing('items');

        $productIds = $order->items->pluck('product_id')->filter()->map(static fn (mixed $id): int => (int) $id)->values()->all();
        $products   = $this->products->lockForUpdate($productIds, withTrashed: true);

        foreach ($order->items as $item) {
            $product = $item->product_id === null ? null : $products->get($item->product_id);

            if ($product === null) {
                continue;
            }

            $product->stock_quantity += $item->quantity;
            $product->sales_count = max(0, $product->sales_count - $item->quantity);
            $product->save();

            $this->log($product, StockMovementType::Return, $item->quantity, order: $order, user: $actor);
        }
    }

    /**
     * Record the opening stock of a newly created product.
     */
    public function setOpeningStock(Product $product, int $quantity, ?User $actor = null): void
    {
        $product->forceFill(['stock_quantity' => max(0, $quantity)])->save();

        if ($quantity > 0) {
            $this->log($product, StockMovementType::Initial, $quantity, user: $actor, note: 'Opening stock');
        }
    }

    /**
     * Apply a manual stock change recorded by an administrator.
     *
     * @throws InventoryException
     */
    public function adjust(Product $product, int $change, StockMovementType $type, ?string $note, ?User $actor): StockMovement
    {
        return DB::transaction(function () use ($product, $change, $type, $note, $actor): StockMovement {
            $locked = $this->lock($product);

            return $this->applyChange($product, $locked, $change, $type, $note, $actor);
        });
    }

    /**
     * Set an absolute stock level, recording the difference as an adjustment.
     */
    public function setLevel(Product $product, int $quantity, ?User $actor, string $note = 'Stock level updated from the product form.'): void
    {
        DB::transaction(function () use ($product, $quantity, $actor, $note): void {
            $locked = $this->lock($product);
            $change = max(0, $quantity) - $locked->stock_quantity;

            if ($change !== 0) {
                $this->applyChange($product, $locked, $change, StockMovementType::Adjustment, $note, $actor);
            }
        });
    }

    private function lock(Product $product): Product
    {
        return $this->products->lockForUpdate([$product->id], withTrashed: true)->get($product->id) ?? $product;
    }

    /**
     * @throws InventoryException
     */
    private function applyChange(Product $product, Product $locked, int $change, StockMovementType $type, ?string $note, ?User $actor): StockMovement
    {
        if ($locked->stock_quantity + $change < 0) {
            throw InventoryException::wouldBeNegative($locked, abs($change));
        }

        $before = $locked->stock_quantity;

        $locked->stock_quantity += $change;
        $locked->save();

        $product->forceFill(['stock_quantity' => $locked->stock_quantity])->syncOriginalAttribute('stock_quantity');

        $movement = $this->log($locked, $type, $change, user: $actor, note: $note);

        $this->notifyIfLow($locked, $before);

        return $movement;
    }

    private function log(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?Order $order = null,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        return $product->stockMovements()->create([
            'order_id'      => $order?->getKey(),
            'user_id'       => $user?->getKey(),
            'type'          => $type,
            'quantity'      => $quantity,
            'balance_after' => $product->stock_quantity,
            'note'          => $note,
        ]);
    }

    /**
     * Fire a single alert when stock crosses below the low stock threshold.
     */
    private function notifyIfLow(Product $product, int $before): void
    {
        $threshold = $product->low_stock_threshold;

        if ($before > $threshold && $product->stock_quantity <= $threshold) {
            $this->events->dispatch(new ProductStockLow($product));
        }
    }
}
