<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ImageRules;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryRequest extends FormRequest
{
    use ImageRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $ignore   = $category instanceof Category ? $category->id : null;

        return [
            'name'             => ['required', 'string', 'max:120'],
            'slug'             => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique(Category::class)->ignore($ignore)],
            'parent_id'        => ['nullable', 'integer', Rule::exists(Category::class, 'id')->whereNull('parent_id'), Rule::notIn(array_filter([$ignore]))],
            'description'      => ['nullable', 'string', 'max:2000'],
            'is_active'        => ['sometimes', 'boolean'],
            'position'         => ['nullable', 'integer', 'min:0', 'max:10000'],
            'meta_title'       => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'image'            => ['nullable', $this->imageRule()],
            'remove_image'     => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return ['parent_id' => 'parent category'];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryAttributes(): array
    {
        return [
            ...$this->safe()->except(['image', 'remove_image']),
            'is_active' => $this->boolean('is_active'),
            'position'  => (int) $this->validated('position', 0),
        ];
    }
}
