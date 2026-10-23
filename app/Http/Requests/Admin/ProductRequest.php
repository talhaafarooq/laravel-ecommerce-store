<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\DTOs\ProductData;
use App\Http\Requests\Concerns\ImageRules;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\ValueObjects\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Validates the product form for both creating and updating products.
 */
final class ProductRequest extends FormRequest
{
    use ImageRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $ignore  = $product instanceof Product ? $product->id : null;

        return [
            'name'                => ['required', 'string', 'max:200'],
            'slug'                => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique(Product::class)->ignore($ignore)],
            'sku'                 => ['required', 'string', 'max:64', Rule::unique(Product::class)->ignore($ignore)],
            'category_id'         => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'brand_id'            => ['nullable', 'integer', Rule::exists(Brand::class, 'id')],
            'short_description'   => ['nullable', 'string', 'max:500'],
            'description'         => ['nullable', 'string', 'max:20000'],
            'price'               => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
            'compare_at_price'    => ['nullable', 'numeric', 'max:1000000', 'decimal:0,2', 'gt:price'],
            'stock_quantity'      => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active'           => ['sometimes', 'boolean'],
            'is_featured'         => ['sometimes', 'boolean'],
            'meta_title'          => ['nullable', 'string', 'max:70'],
            'meta_description'    => ['nullable', 'string', 'max:160'],
            'images'              => ['nullable', 'array', 'max:' . config('shop.images.max_per_upload', 8)],
            'images.*'            => [$this->imageRule()],
        ];
    }

    public function toData(): ProductData
    {
        $compareAt = $this->validated('compare_at_price');

        return new ProductData(
            name: (string) $this->validated('name'),
            slug: $this->validated('slug'),
            sku: strtoupper((string) $this->validated('sku')),
            categoryId: (int) $this->validated('category_id'),
            brandId: $this->filled('brand_id') ? (int) $this->validated('brand_id') : null,
            shortDescription: $this->validated('short_description'),
            description: $this->validated('description'),
            price: Money::fromDecimal((string) $this->validated('price')),
            compareAtPrice: $compareAt === null ? null : Money::fromDecimal((string) $compareAt),
            stockQuantity: (int) $this->validated('stock_quantity'),
            lowStockThreshold: (int) $this->validated('low_stock_threshold'),
            isActive: $this->boolean('is_active'),
            isFeatured: $this->boolean('is_featured'),
            metaTitle: $this->validated('meta_title'),
            metaDescription: $this->validated('meta_description'),
        );
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedImages(): array
    {
        return array_values(array_filter(
            (array) $this->file('images', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }
}
