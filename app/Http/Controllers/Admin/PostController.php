<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request, PostRepositoryInterface $repository): Response
    {
        $search = $this->searchTerm($request);

        return Inertia::render('admin/posts/index', [
            'posts'   => PostResource::collection($repository->paginateForAdmin($search)),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/form', ['post' => null]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = $this->posts->create($request->postAttributes(), $request->file('cover'), $request->user());

        $this->toast(__('Post ":title" created.', ['title' => $post->title]));

        return to_route('admin.posts.edit', $post);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('admin/posts/form', [
            'post' => PostResource::make($post)->withSource()->resolve(),
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->update($post, $request->postAttributes(), $request->file('cover'), $request->boolean('remove_cover'));

        $this->toast(__('Post ":title" updated.', ['title' => $post->title]));

        return to_route('admin.posts.edit', $post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->posts->delete($post);

        $this->toast(__('Post ":title" deleted.', ['title' => $post->title]));

        return to_route('admin.posts.index');
    }
}
