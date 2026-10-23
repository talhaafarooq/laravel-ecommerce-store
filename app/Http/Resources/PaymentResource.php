<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
final class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'             => $this->id,
            'gateway'        => $this->gateway->toOption(),
            'status'         => $this->status->toOption(),
            'amount'         => $this->amount,
            'reference'      => $this->when($user instanceof User && $user->isAdmin(), $this->reference),
            'failure_reason' => $this->failure_reason,
            'paid_at'        => $this->paid_at?->toIso8601String(),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
