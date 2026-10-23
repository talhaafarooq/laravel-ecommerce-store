<?php

declare(strict_types = 1);

namespace App\Repositories;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\Models\Post;
use App\Repositories\Concerns\BuildsLikeQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PostRepository implements PostRepositoryInterface
{
    use BuildsLikeQueries;

    public function paginatePublished(?string $search = null, int $perPage = 9): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('author:id,name,avatar_path')
            ->when($search !== null, function (Builder $query) use ($search): void {
                $like = $this->contains((string) $search);

                $query->where(static fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->published()
            ->where('slug', $slug)
            ->with('author:id,name,avatar_path')
            ->first();
    }

    public function latestPublished(int $limit, ?Post $except = null): Collection
    {
        return Post::query()
            ->published()
            ->with('author:id,name,avatar_path')
            ->when($except !== null, static fn (Builder $query) => $query->whereKeyNot($except?->getKey()))
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function paginateForAdmin(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->with('author:id,name')
            ->when($search !== null, fn (Builder $query) => $query->where('title', 'like', $this->contains((string) $search)))
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
