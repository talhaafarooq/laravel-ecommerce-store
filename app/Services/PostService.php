<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class PostService
{
    private const string DIRECTORY = 'posts';

    public function __construct(private ImageService $images) {}

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes, ?UploadedFile $cover, User $author): Post
    {
        if ($cover !== null) {
            $attributes['cover_image_path'] = $this->images->store($cover, self::DIRECTORY, 1600, 900);
        }

        return Post::query()->create([...$attributes, 'author_id' => $author->id]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Post $post, array $attributes, ?UploadedFile $cover = null, bool $removeCover = false): Post
    {
        $previousCover = $post->cover_image_path;

        if ($cover !== null) {
            $attributes['cover_image_path'] = $this->images->store($cover, self::DIRECTORY, 1600, 900);
        } elseif ($removeCover) {
            $attributes['cover_image_path'] = null;
        }

        $post->update($attributes);

        if ($previousCover !== null && $previousCover !== $post->cover_image_path) {
            $this->images->delete($previousCover);
        }

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();

        $this->images->delete($post->cover_image_path);
    }
}
