<?php

declare(strict_types = 1);

namespace App\Http\Requests\Storefront;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReviewRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title'  => ['nullable', 'string', 'max:120'],
            'body'   => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array{rating: int, title: string|null, body: string}
     */
    public function reviewData(): array
    {
        return [
            'rating' => (int) $this->validated('rating'),
            'title'  => $this->validated('title'),
            'body'   => (string) $this->validated('body'),
        ];
    }
}
