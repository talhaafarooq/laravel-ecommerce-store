<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Exceptions\CategoryInUseException;
use App\Models\Category;
use Illuminate\Http\UploadedFile;

final readonly class CategoryService
{
    private const string DIRECTORY = 'categories';

    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ImageService $images,
        private CatalogService $catalog,
    ) {}

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes, ?UploadedFile $image = null): Category
    {
        if ($image !== null) {
            $attributes['image_path'] = $this->images->store($image, self::DIRECTORY, 1200);
        }

        $category = Category::query()->create($attributes);

        $this->catalog->flush();

        return $category;
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws CategoryInUseException
     */
    public function update(Category $category, array $attributes, ?UploadedFile $image = null, bool $removeImage = false): Category
    {
        $parentId = isset($attributes['parent_id']) ? (int) $attributes['parent_id'] : null;

        if ($parentId !== null && in_array($parentId, $this->categories->descendantIdsWithSelf($category, activeOnly: false), true)) {
            throw CategoryInUseException::invalidParent();
        }

        $previousImage = $category->image_path;

        if ($image !== null) {
            $attributes['image_path'] = $this->images->store($image, self::DIRECTORY, 1200);
        } elseif ($removeImage) {
            $attributes['image_path'] = null;
        }

        $category->update($attributes);

        if ($previousImage !== null && $previousImage !== $category->image_path) {
            $this->images->delete($previousImage);
        }

        $this->catalog->flush();

        return $category;
    }

    /**
     * @throws CategoryInUseException
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw CategoryInUseException::hasChildren();
        }

        if ($category->products()->withTrashed()->exists()) {
            throw CategoryInUseException::hasProducts();
        }

        $category->delete();

        $this->images->delete($category->image_path);
        $this->catalog->flush();
    }
}
