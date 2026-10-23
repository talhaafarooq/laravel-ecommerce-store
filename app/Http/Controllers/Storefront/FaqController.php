<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

final class FaqController extends Controller
{
    public function __invoke(): Response
    {
        $faqs = Faq::query()->published()->ordered()->get();

        return Inertia::render('storefront/faq', [
            'topics' => $faqs->groupBy('topic')
                ->map(static fn ($items, string $topic): array => [
                    'topic' => $topic,
                    'items' => FaqResource::collection($items)->resolve(),
                ])
                ->values(),
        ]);
    }
}
