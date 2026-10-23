<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ImageRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class ProductImageRequest extends FormRequest
{
    use ImageRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images'   => ['required', 'array', 'min:1', 'max:' . config('shop.images.max_per_upload', 8)],
            'images.*' => [$this->imageRule()],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedImages(): array
    {
        return array_values(array_filter(
            (array) $this->file('images', []),
            static fn (mixed $file): bool => $file instanceof UploadedFile,
        ));
    }
}
