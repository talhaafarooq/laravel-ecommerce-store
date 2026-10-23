<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BlogController extends Controller
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request, 'q');

        return Inertia::render('storefront/blog/index', [
            'posts'  => PostResource::collection($this->posts->paginatePublished($search)),
            'search' => $search,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = $this->posts->findPublishedBySlug($slug)
            ?? throw (new ModelNotFoundException)->setModel(Post::class, [$slug]);

        return Inertia::render('storefront/blog/show', [
            'post'    => PostResource::make($post)->withBody()->resolve(),
            'related' => PostResource::collection($this->posts->latestPublished(3, except: $post))->resolve(),
        ]);
    }
}
