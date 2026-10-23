<?php

declare(strict_types = 1);

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\ValueObjects\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CouponRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code'                 => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique(Coupon::class)->ignore($coupon instanceof Coupon ? $coupon->id : null)],
            'description'          => ['nullable', 'string', 'max:255'],
            'type'                 => ['required', Rule::enum(CouponType::class)],
            'value'                => ['required', 'numeric', 'min:0.01', 'decimal:0,2', Rule::when($this->input('type') === CouponType::Percentage->value, ['max:100', 'integer'])],
            'max_discount'         => ['nullable', 'numeric', 'min:0.01', 'decimal:0,2'],
            'min_subtotal'         => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'usage_limit'          => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at'            => ['nullable', 'date'],
            'expires_at'           => ['nullable', 'date', 'after:starts_at'],
            'is_active'            => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Percentage coupons store whole percents; fixed coupons store minor units.
     *
     * @return array<string, mixed>
     */
    public function couponAttributes(): array
    {
        $type = CouponType::from((string) $this->validated('type'));

        return [
            ...$this->safe()->except(['value', 'max_discount', 'min_subtotal', 'is_active']),
            'type'  => $type,
            'value' => $type === CouponType::Percentage
                ? (int) $this->validated('value')
                : Money::fromDecimal((string) $this->validated('value'))->amount,
            'max_discount' => $this->filled('max_discount') ? Money::fromDecimal((string) $this->validated('max_discount')) : null,
            'min_subtotal' => $this->filled('min_subtotal') ? Money::fromDecimal((string) $this->validated('min_subtotal')) : null,
            'is_active'    => $this->boolean('is_active'),
        ];
    }
}
