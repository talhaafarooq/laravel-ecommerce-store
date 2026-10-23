<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockMovement
 */
final class StockMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type->toOption(),
            'quantity'      => $this->quantity,
            'balance_after' => $this->balance_after,
            'note'          => $this->note,
            'user'          => $this->relationLoaded('user') ? $this->user?->name : null,
            'order_number'  => $this->relationLoaded('order') ? $this->order?->number : null,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
