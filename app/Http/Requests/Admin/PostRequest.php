<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ImageRules;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PostRequest extends FormRequest
{
    use ImageRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique(Post::class)->ignore($post instanceof Post ? $post->id : null)],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'body'             => ['required', 'string', 'max:100000'],
            'published_at'     => ['nullable', 'date'],
            'meta_title'       => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'cover'            => ['nullable', $this->imageRule()],
            'remove_cover'     => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function postAttributes(): array
    {
        return $this->safe()->except(['cover', 'remove_cover']);
    }
}
