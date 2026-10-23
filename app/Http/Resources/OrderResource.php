<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'               => $this->id,
            'number'           => $this->number,
            'status'           => [...$this->status->toOption(), 'tone' => $this->status->tone(), 'step' => $this->status->step()],
            'payment_status'   => [...$this->payment_status->toOption(), 'tone' => $this->payment_status->tone()],
            'payment_method'   => $this->payment_method->toOption(),
            'currency'         => $this->currency,
            'subtotal'         => $this->subtotal,
            'discount_total'   => $this->discount_total,
            'shipping_total'   => $this->shipping_total,
            'tax_total'        => $this->tax_total,
            'grand_total'      => $this->grand_total,
            'coupon_code'      => $this->coupon_code,
            'customer_email'   => $this->customer_email,
            'notes'            => $this->notes,
            'shipping_address' => [
                'name'        => $this->shipping_name,
                'phone'       => $this->shipping_phone,
                'line1'       => $this->shipping_line1,
                'line2'       => $this->shipping_line2,
                'city'        => $this->shipping_city,
                'state'       => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country'     => $this->shipping_country,
                'lines'       => $this->shippingAddressLines(),
            ],
            'items_count' => $this->whenCounted('items'),
            'items'       => OrderItemResource::collection($this->whenLoaded('items')),
            'timeline'    => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'payments'    => PaymentResource::collection($this->whenLoaded('payments')),
            'customer'    => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'cancellation_reason' => $this->cancellation_reason,
            'placed_at'           => $this->placed_at->toIso8601String(),
            'paid_at'             => $this->paid_at?->toIso8601String(),
            'shipped_at'          => $this->shipped_at?->toIso8601String(),
            'delivered_at'        => $this->delivered_at?->toIso8601String(),
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),
            'allowed_transitions' => array_map(
                static fn ($status): array => $status->toOption(),
                $this->status->allowedTransitions(),
            ),
            'can' => [
                'cancel' => $user instanceof User && $user->can('cancel', $this->resource),
                'pay'    => $user instanceof User && $user->can('pay', $this->resource),
            ],
        ];
    }
}
