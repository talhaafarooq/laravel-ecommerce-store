<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryController extends Controller
{
    public function index(ProductIndexRequest $request, ProductRepositoryInterface $products, CategoryRepositoryInterface $categories): Response
    {
        $filters = $request->filters();

        return Inertia::render('admin/inventory', [
            'products'        => AdminProductResource::collection($products->paginateForAdmin($filters)),
            'filters'         => $filters->toArray(),
            'categories'      => $categories->options(),
            'stockStatuses'   => StockStatus::options(),
            'adjustmentTypes' => array_map(static fn (StockMovementType $type): array => $type->toOption(), StockMovementType::manual()),
            'movements'       => Inertia::defer(fn (): array => StockMovementResource::collection(
                StockMovement::query()
                    ->with(['product:id,name,sku,slug,deleted_at', 'user:id,name', 'order:id,number'])
                    ->latest('id')
                    ->limit(15)
                    ->get(),
            )->resolve()),
        ]);
    }

    public function adjust(StockAdjustmentRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $movement = $inventory->adjust(
            $product,
            (int) $request->validated('quantity'),
            $request->type(),
            $request->validated('note'),
            $request->user(),
        );

        $this->toast(__('Stock for :name is now :balance.', ['name' => $product->name, 'balance' => $movement->balance_after]));

        return back();
    }
}
