<?php

declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\Markdown;
use App\Support\Media;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                  $id
 * @property int|null             $author_id
 * @property string               $title
 * @property string               $slug
 * @property string|null          $excerpt
 * @property string               $body
 * @property string|null          $cover_image_path
 * @property string|null          $cover_image_url
 * @property CarbonImmutable|null $published_at
 * @property string|null          $meta_title
 * @property string|null          $meta_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property User|null            $author
 */
#[Fillable(['author_id', 'title', 'slug', 'excerpt', 'body', 'cover_image_path', 'published_at', 'meta_title', 'meta_description'])]
#[RouteKey('slug')]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasSlug;

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function bodyHtml(): string
    {
        return Markdown::toHtml($this->body);
    }

    public function readingTime(): int
    {
        return Markdown::readingTime($this->body);
    }

    /**
     * @param Builder<self> $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => Media::url($this->cover_image_path));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
