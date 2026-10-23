<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusHistory
 */
final class OrderStatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'from'       => $this->from_status?->toOption(),
            'to'         => [...$this->to_status->toOption(), 'tone' => $this->to_status->tone()],
            'note'       => $this->note,
            'actor'      => $this->relationLoaded('user') ? $this->user?->name : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
