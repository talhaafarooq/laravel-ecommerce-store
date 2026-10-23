<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StockAdjustmentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'     => ['required', Rule::in(array_map(static fn (StockMovementType $type): string => $type->value, StockMovementType::manual()))],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function type(): StockMovementType
    {
        return StockMovementType::from((string) $this->validated('type'));
    }
}
