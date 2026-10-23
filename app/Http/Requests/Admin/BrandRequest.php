<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BrandRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $brand = $this->route('brand');

        return [
            'name'        => ['required', 'string', 'max:120', Rule::unique(Brand::class)->ignore($brand instanceof Brand ? $brand->id : null)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function brandAttributes(): array
    {
        return [...$this->validated(), 'is_active' => $this->boolean('is_active')];
    }
}
