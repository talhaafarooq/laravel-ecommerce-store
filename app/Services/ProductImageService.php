<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manages the image gallery of a product.
 *
 * Files are written before any database change and removed again when the
 * change fails, so the disk never accumulates orphaned uploads.
 */
final readonly class ProductImageService
{
    private const string DIRECTORY = 'products';

    public function __construct(private ImageService $images) {}

    /**
     * Process uploads into stored files without touching the database.
     *
     * @param list<UploadedFile|string> $sources
     *
     * @return list<array{path: string, thumbnail_path: string}>
     */
    public function storeFiles(array $sources): array
    {
        $stored = [];

        try {
            foreach ($sources as $source) {
                $stored[] = $this->images->storeWithThumbnail($source, self::DIRECTORY);
            }
        } catch (Throwable $exception) {
            $this->discardFiles($stored);

            throw $exception;
        }

        return $stored;
    }

    /**
     * Attach stored files to the end of the product's gallery.
     *
     * @param list<array{path: string, thumbnail_path: string}> $stored
     */
    public function attach(Product $product, array $stored, ?string $alt = null): void
    {
        $position = (int) $product->images()->max('position') + ($product->images()->exists() ? 1 : 0);

        foreach ($stored as $file) {
            $product->images()->create([
                'path'           => $file['path'],
                'thumbnail_path' => $file['thumbnail_path'],
                'alt'            => $alt ?? $product->name,
                'position'       => $position++,
            ]);
        }
    }

    /**
     * Upload images and append them to the gallery.
     *
     * @param list<UploadedFile> $uploads
     */
    public function upload(Product $product, array $uploads): void
    {
        $stored = $this->storeFiles($uploads);

        try {
            DB::transaction(fn () => $this->attach($product, $stored));
        } catch (Throwable $exception) {
            $this->discardFiles($stored);

            throw $exception;
        }
    }

    public function delete(ProductImage $image): void
    {
        DB::transaction(function () use ($image): void {
            $productId = $image->product_id;

            $image->delete();

            $this->resequence($productId);
        });

        $this->images->delete($image->path, $image->thumbnail_path);
    }

    /**
     * Make the image the product's cover by moving it to the front.
     */
    public function makePrimary(ProductImage $image): void
    {
        DB::transaction(function () use ($image): void {
            $ids = ProductImage::query()
                ->where('product_id', $image->product_id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->reject(static fn (mixed $id): bool => (int) $id === $image->id)
                ->prepend($image->id)
                ->map(static fn (mixed $id): int => (int) $id)
                ->values()
                ->all();

            $this->applyOrder($ids);
        });
    }

    /**
     * Delete every stored file of a product (used when it is permanently removed).
     */
    public function deleteAllFor(Product $product): void
    {
        $product->loadMissing('images');

        foreach ($product->images as $image) {
            $this->images->delete($image->path, $image->thumbnail_path);
        }
    }

    /**
     * @param list<array{path: string, thumbnail_path: string}> $stored
     */
    public function discardFiles(array $stored): void
    {
        foreach ($stored as $file) {
            $this->images->delete($file['path'], $file['thumbnail_path']);
        }
    }

    private function resequence(int $productId): void
    {
        $ids = ProductImage::query()
            ->where('product_id', $productId)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->applyOrder($ids);
    }

    /**
     * @param array<int, int> $ids
     */
    private function applyOrder(array $ids): void
    {
        foreach ($ids as $position => $id) {
            ProductImage::query()->whereKey($id)->update(['position' => $position]);
        }
    }
}
