<?php

declare(strict_types = 1);

namespace App\Contracts\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginatePublished(?string $search = null, int $perPage = 9): LengthAwarePaginator;

    public function findPublishedBySlug(string $slug): ?Post;

    /**
     * @return Collection<int, Post>
     */
    public function latestPublished(int $limit, ?Post $except = null): Collection;

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginateForAdmin(?string $search = null, int $perPage = 15): LengthAwarePaginator;
}
