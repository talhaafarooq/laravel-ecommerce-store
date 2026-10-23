<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\ValueObjects\DateRange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SalesReportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function range(): DateRange
    {
        return DateRange::fromStrings($this->validated('from'), $this->validated('to'));
    }
}
