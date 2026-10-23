<?php

declare(strict_types = 1);

namespace App\Http\Requests\Storefront;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
{
    /**
     * Stock and availability are enforced by the cart service.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity'   => ['sometimes', 'integer', 'min:1', 'max:' . config('shop.cart.max_quantity_per_item', 10)],
        ];
    }
}
