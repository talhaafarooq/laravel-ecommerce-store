<?php

declare(strict_types = 1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rules for a postal address, optionally nested under a key.
 */
trait AddressRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function addressRules(string $prefix = ''): array
    {
        return [
            "{$prefix}full_name"   => ['required', 'string', 'max:120'],
            "{$prefix}phone"       => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s().]{6,30}$/'],
            "{$prefix}line1"       => ['required', 'string', 'max:255'],
            "{$prefix}line2"       => ['nullable', 'string', 'max:255'],
            "{$prefix}city"        => ['required', 'string', 'max:120'],
            "{$prefix}state"       => ['nullable', 'string', 'max:120'],
            "{$prefix}postal_code" => ['required', 'string', 'max:20'],
            "{$prefix}country"     => ['required', 'string', 'size:2', 'alpha'],
        ];
    }
}
