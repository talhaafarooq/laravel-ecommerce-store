<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'avatar'             => $this->avatar_url,
            'role'               => $this->role->toOption(),
            'is_admin'           => $this->isAdmin(),
            'is_active'          => $this->is_active,
            'email_verified_at'  => $this->email_verified_at?->toIso8601String(),
            'two_factor_enabled' => $this->two_factor_confirmed_at !== null,
            'last_login_at'      => $this->last_login_at?->toIso8601String(),
            'created_at'         => $this->created_at?->toIso8601String(),
            'orders_count'       => $this->whenCounted('orders'),
            'total_spent'        => $this->when(
                array_key_exists('total_spent', $this->resource->getAttributes()),
                fn (): Money => Money::of((int) $this->resource->getAttribute('total_spent')),
            ),
        ];
    }
}
