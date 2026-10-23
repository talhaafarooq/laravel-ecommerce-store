<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
final class PostResource extends JsonResource
{
    private bool $withBody = false;

    private bool $withSource = false;

    /**
     * Include the rendered article body.
     */
    public function withBody(): self
    {
        $this->withBody = true;

        return $this;
    }

    /**
     * Include the raw Markdown source for editing.
     */
    public function withSource(): self
    {
        $this->withSource = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'excerpt'          => $this->excerpt,
            'cover'            => $this->cover_image_url,
            'published_at'     => $this->published_at?->toIso8601String(),
            'is_published'     => $this->isPublished(),
            'reading_time'     => $this->readingTime(),
            'meta_title'       => $this->meta_title       ?? $this->title,
            'meta_description' => $this->meta_description ?? $this->excerpt,
            'author'           => $this->whenLoaded('author', fn (): ?array => $this->author === null ? null : [
                'name'   => $this->author->name,
                'avatar' => $this->author->avatar_url,
            ]),
            'body_html' => $this->when($this->withBody, fn (): string => $this->bodyHtml()),
            'body'      => $this->when($this->withSource, fn (): string => $this->body),
        ];
    }
}
