<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Faq
 */
final class FaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'topic'        => $this->topic,
            'question'     => $this->question,
            'answer'       => $this->answer,
            'position'     => $this->position,
            'is_published' => $this->is_published,
        ];
    }
}
