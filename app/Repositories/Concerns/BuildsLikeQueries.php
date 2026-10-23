<?php

declare(strict_types = 1);

namespace App\Repositories\Concerns;

/**
 * Helpers for building LIKE patterns from untrusted input.
 *
 * Wildcard characters supplied by users are escaped so they match literally.
 */
trait BuildsLikeQueries
{
    protected function contains(string $value): string
    {
        return '%' . $this->escapeLike($value) . '%';
    }

    protected function startsWith(string $value): string
    {
        return $this->escapeLike($value) . '%';
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Split a search phrase into a bounded list of distinct terms.
     *
     * @return list<string>
     */
    protected function searchTerms(string $phrase, int $limit = 5): array
    {
        $terms = preg_split('/\s+/', trim($phrase)) ?: [];

        return array_slice(array_values(array_unique(array_filter(
            $terms,
            static fn (string $term): bool => mb_strlen($term) > 0,
        ))), 0, $limit);
    }
}
