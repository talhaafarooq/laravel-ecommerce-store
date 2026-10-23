<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CatalogService;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;

final class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $images,
        private readonly CatalogService $catalog,
    ) {}

    public function store(ProductImageRequest $request, Product $product): RedirectResponse
    {
        $this->images->upload($product, $request->uploadedImages());
        $this->catalog->flush();

        $this->toast(__('Images uploaded.'));

        return back();
    }

    public function primary(Product $product, ProductImage $image): RedirectResponse
    {
        $this->images->makePrimary($image);
        $this->catalog->flush();

        $this->toast(__('Primary image updated.'));

        return back();
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        $this->images->delete($image);
        $this->catalog->flush();

        $this->toast(__('Image deleted.'));

        return back();
    }
}
