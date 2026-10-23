<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
final class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id'                   => $this->id,
            'rating'               => $this->rating,
            'title'                => $this->title,
            'body'                 => $this->body,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_approved'          => $this->is_approved,
            'created_at'           => $this->created_at?->toIso8601String(),
            'author'               => [
                'id'     => $user?->id,
                'name'   => $user->name ?? __('Former customer'),
                'avatar' => $user?->avatar_url,
            ],
            'product' => $this->whenLoaded('product', fn (): array => [
                'id'      => $this->product->id,
                'name'    => $this->product->name,
                'slug'    => $this->product->slug,
                'trashed' => $this->product->trashed(),
            ]),
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}
