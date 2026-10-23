<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(Request $request, CategoryRepositoryInterface $repository): Response
    {
        $search = $this->searchTerm($request);

        return Inertia::render('admin/categories', [
            'categories' => CategoryResource::collection($repository->allForAdmin($search))->resolve(),
            'parents'    => $repository->options()->whereNull('parent_id')->values(),
            'filters'    => ['search' => $search],
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->categoryAttributes(), $request->file('image'));

        $this->toast(__('Category :name created.', ['name' => $category->name]));

        return back();
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->categoryAttributes(), $request->file('image'), $request->boolean('remove_image'));

        $this->toast(__('Category :name updated.', ['name' => $category->name]));

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        $this->toast(__('Category :name deleted.', ['name' => $category->name]));

        return back();
    }
}
