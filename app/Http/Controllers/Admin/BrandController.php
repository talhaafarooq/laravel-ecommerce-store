<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Brands are simple reference data, so they are managed through the model directly.
 */
final class BrandController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(): Response
    {
        return Inertia::render('admin/brands', [
            'brands' => BrandResource::collection(Brand::query()->withCount('products')->orderBy('name')->get())->resolve(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::query()->create($request->brandAttributes());
        $this->catalog->flush();

        $this->toast(__('Brand :name created.', ['name' => $brand->name]));

        return back();
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->brandAttributes());
        $this->catalog->flush();

        $this->toast(__('Brand :name updated.', ['name' => $brand->name]));

        return back();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        // Products keep existing without a brand (brand_id is nulled on delete).
        $brand->delete();
        $this->catalog->flush();

        $this->toast(__('Brand :name deleted.', ['name' => $brand->name]));

        return back();
    }
}
