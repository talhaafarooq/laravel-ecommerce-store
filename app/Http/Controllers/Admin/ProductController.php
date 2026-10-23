<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function index(ProductIndexRequest $request, ProductRepositoryInterface $repository): Response
    {
        $filters = $request->filters();

        return Inertia::render('admin/products/index', [
            'products'      => AdminProductResource::collection($repository->paginateForAdmin($filters)),
            'filters'       => $filters->toArray(),
            'categories'    => $this->categories->options(),
            'stockStatuses' => StockStatus::options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->toData(), $request->uploadedImages(), $request->user());

        $this->toast(__('Product :name created.', ['name' => $product->name]));

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'category:id,name', 'brand:id,name']);

        return Inertia::render('admin/products/form', [
            'product'        => AdminProductResource::make($product)->resolve(),
            'stockMovements' => Inertia::defer(fn (): array => StockMovementResource::collection(
                $product->stockMovements()->with(['user:id,name', 'order:id,number'])->latest('id')->limit(20)->get(),
            )->resolve()),
            ...$this->formOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->toData(), $request->uploadedImages(), $request->user());

        $this->toast(__('Product :name updated.', ['name' => $product->name]));

        return to_route('admin.products.edit', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->archive($product);

        $this->toast(__('Product :name archived.', ['name' => $product->name]));

        return to_route('admin.products.index');
    }

    public function restore(Product $product): RedirectResponse
    {
        $this->products->restore($product);

        $this->toast(__('Product :name restored.', ['name' => $product->name]));

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => $this->categories->options(),
            'brands'     => Brand::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
