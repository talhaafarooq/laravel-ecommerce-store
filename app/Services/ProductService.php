<?php

declare(strict_types = 1);

namespace App\Services;

use App\DTOs\ProductData;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Administrative product management.
 */
final readonly class ProductService
{
    public function __construct(
        private InventoryService $inventory,
        private ProductImageService $images,
        private CatalogService $catalog,
    ) {}

    /**
     * @param list<UploadedFile> $uploads
     */
    public function create(ProductData $data, array $uploads, ?User $actor): Product
    {
        $stored = $this->images->storeFiles($uploads);

        try {
            $product = DB::transaction(function () use ($data, $stored, $actor): Product {
                $product = Product::query()->create([...$data->toAttributes(), 'stock_quantity' => 0]);

                $this->inventory->setOpeningStock($product, $data->stockQuantity, $actor);
                $this->images->attach($product, $stored);

                return $product;
            });
        } catch (Throwable $exception) {
            $this->images->discardFiles($stored);

            throw $exception;
        }

        $this->catalog->flush();

        return $product;
    }

    /**
     * @param list<UploadedFile> $uploads
     */
    public function update(Product $product, ProductData $data, array $uploads, ?User $actor): Product
    {
        $stored = $this->images->storeFiles($uploads);

        try {
            DB::transaction(function () use ($product, $data, $stored, $actor): void {
                $product->update($data->toAttributes());

                $this->inventory->setLevel($product, $data->stockQuantity, $actor);
                $this->images->attach($product, $stored);
            });
        } catch (Throwable $exception) {
            $this->images->discardFiles($stored);

            throw $exception;
        }

        $this->catalog->flush();

        return $product;
    }

    /**
     * Archive the product. Order history keeps referencing it.
     */
    public function archive(Product $product): void
    {
        $product->delete();

        $this->catalog->flush();
    }

    public function restore(Product $product): void
    {
        $product->restore();

        $this->catalog->flush();
    }
}
