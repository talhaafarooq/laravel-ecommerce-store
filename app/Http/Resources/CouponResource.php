<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Coupon
 */
final class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'code'                 => $this->code,
            'description'          => $this->description,
            'type'                 => $this->type->toOption(),
            'value'                => $this->type === CouponType::Fixed ? Money::of($this->value)->toDecimal() : (string) $this->value,
            'summary'              => $this->summary(),
            'max_discount'         => $this->max_discount,
            'min_subtotal'         => $this->min_subtotal,
            'usage_limit'          => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'times_used'           => $this->times_used,
            'starts_at'            => $this->starts_at?->toIso8601String(),
            'expires_at'           => $this->expires_at?->toIso8601String(),
            'is_active'            => $this->is_active,
            'state'                => $this->state(),
        ];
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function state(): array
    {
        return match (true) {
            !$this->is_active    => ['label' => 'Inactive', 'tone' => 'neutral'],
            $this->isExpired()   => ['label' => 'Expired', 'tone' => 'danger'],
            $this->isExhausted() => ['label' => 'Used up', 'tone' => 'warning'],
            !$this->hasStarted() => ['label' => 'Scheduled', 'tone' => 'info'],
            default              => ['label' => 'Active', 'tone' => 'success'],
        };
    }
}
