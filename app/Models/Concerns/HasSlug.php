<?php

declare(strict_types = 1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Generates a unique, URL-friendly slug when one is not provided.
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(static function (Model $model): void {
            /** @var Model&self $model */
            if (blank($model->getAttribute('slug'))) {
                $model->setAttribute('slug', static::uniqueSlug(
                    (string) $model->getAttribute($model->slugSource()),
                    $model->getKey(),
                ));
            }
        });
    }

    /**
     * Build a slug from the given value that is unique for this model.
     */
    public static function uniqueSlug(string $value, int|string|null $ignoreKey = null): string
    {
        $base      = Str::slug($value) ?: Str::lower(Str::random(8));
        $base      = Str::limit($base, 120, '');
        $candidate = $base;
        $suffix    = 2;

        while (static::slugExists($candidate, $ignoreKey)) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * The attribute the slug is derived from.
     */
    protected function slugSource(): string
    {
        return 'name';
    }

    protected static function slugExists(string $slug, int|string|null $ignoreKey): bool
    {
        $query = static::query()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withoutGlobalScopes();
        }

        if ($ignoreKey !== null) {
            $query->whereKeyNot($ignoreKey);
        }

        return $query->exists();
    }
}
