<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProductImageService;
use App\Support\Media;
use App\ValueObjects\Money;
use Database\Seeders\Support\RemoteImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Seeds categories, brands and products from database/data/catalog.json.
 *
 * Product photos come from the DummyJSON demo CDN and are processed by the
 * same image pipeline as admin uploads (WebP + thumbnails).
 */
final class CatalogSeeder extends Seeder
{
    /**
     * The first photo seen for each category, used as its cover image.
     *
     * @var array<int, string>
     */
    private array $covers = [];

    public function __construct(
        private readonly RemoteImages $remote,
        private readonly ProductImageService $productImages,
        private readonly ImageService $imageService,
    ) {}

    public function run(): void
    {
        /** @var array{categories: list<array<string, mixed>>, products: list<array<string, mixed>>} $catalog */
        $catalog = File::json(database_path('data/catalog.json'));

        $this->clearMedia();

        $categories = $this->seedCategories($catalog['categories']);
        $brands     = $this->seedBrands($catalog['products']);
        $admin      = User::query()->where('email', UserSeeder::ADMIN_EMAIL)->first();

        $this->command->getOutput()->progressStart(count($catalog['products']));

        foreach (array_chunk($catalog['products'], 12) as $chunk) {
            $urls = [];

            foreach ($chunk as $index => $item) {
                foreach ($item['images'] as $position => $url) {
                    $urls["{$index}:{$position}"] = $url;
                }
            }

            $binaries = $this->remote->fetchMany($urls);

            foreach ($chunk as $index => $item) {
                $images = array_values(array_filter(
                    $binaries,
                    static fn (string $key): bool => str_starts_with($key, "{$index}:"),
                    ARRAY_FILTER_USE_KEY,
                ));

                $this->seedProduct($item, $categories, $brands, $images, $admin);
                $this->command->getOutput()->progressAdvance();
            }
        }

        $this->command->getOutput()->progressFinish();

        $this->seedCategoryCovers();
    }

    private function seedCategoryCovers(): void
    {
        Category::query()->with('children:id,parent_id')->get()->each(function (Category $category): void {
            $cover = $this->covers[$category->id]
                ?? $category->children->map(fn (Category $child): ?string => $this->covers[$child->id] ?? null)->filter()->first();

            if (is_string($cover)) {
                $category->update(['image_path' => $this->imageService->store($cover, 'categories', 1200)]);
            }
        });
    }

    /**
     * @param list<array<string, mixed>> $tree
     *
     * @return array<string, int> Child category ids keyed by catalogue key.
     */
    private function seedCategories(array $tree): array
    {
        $ids = [];

        foreach ($tree as $root) {
            $parent = Category::query()->create([
                'name'             => $root['name'],
                'slug'             => $root['slug'],
                'description'      => $root['description'],
                'position'         => $root['position'],
                'is_active'        => true,
                'meta_description' => $root['description'],
            ]);

            foreach ($root['children'] as $child) {
                $ids[$child['key']] = Category::query()->create([
                    'parent_id' => $parent->id,
                    'name'      => $child['name'],
                    'slug'      => $child['slug'],
                    'position'  => $child['position'],
                    'is_active' => true,
                ])->id;
            }
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $products
     *
     * @return array<string, int>
     */
    private function seedBrands(array $products): array
    {
        return collect($products)
            ->pluck('brand')
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(static fn (string $name): array => [$name => Brand::query()->create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'is_active' => true,
            ])->id])
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, int>   $categories
     * @param array<string, int>   $brands
     * @param list<string>         $images
     */
    private function seedProduct(array $item, array $categories, array $brands, array $images, ?User $admin): void
    {
        $price     = Money::fromDecimal((string) $item['price']);
        $compareAt = $item['compare_at_price'] === null ? null : Money::fromDecimal((string) $item['compare_at_price']);
        $stock     = (int) $item['stock'];

        $product = Product::query()->create([
            'category_id'         => $categories[$item['category']],
            'brand_id'            => $item['brand'] === null ? null : $brands[$item['brand']],
            'name'                => $item['name'],
            'slug'                => $item['slug'],
            'sku'                 => $item['sku'],
            'short_description'   => $item['short_description'],
            'description'         => $item['description'],
            'price'               => $price,
            'compare_at_price'    => $compareAt !== null && $compareAt->greaterThan($price) ? $compareAt : null,
            'low_stock_threshold' => 5,
            'is_active'           => true,
            'is_featured'         => (bool) $item['featured'],
            'meta_description'    => Str::limit((string) $item['short_description'], 155),
        ]);

        $product->forceFill([
            'stock_quantity' => $stock,
            'created_at'     => now()->subDays(random_int(0, 120))->subMinutes(random_int(0, 1440)),
        ])->save();

        $product->stockMovements()->create([
            'user_id'       => $admin?->id,
            'type'          => StockMovementType::Initial,
            'quantity'      => $stock,
            'balance_after' => $stock,
            'note'          => 'Opening stock',
        ]);

        if ($images !== []) {
            $this->covers[$product->category_id] ??= $images[0];
            $this->productImages->attach($product, $this->productImages->storeFiles($images));
        }
    }

    /**
     * Remove media left over from a previous seed of an empty database.
     */
    private function clearMedia(): void
    {
        foreach (['products', 'categories', 'posts', 'avatars'] as $directory) {
            Media::disk()->deleteDirectory($directory);
        }
    }
}
