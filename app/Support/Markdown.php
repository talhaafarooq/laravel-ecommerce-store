<?php

declare(strict_types = 1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Renders user-authored Markdown into HTML that is safe to output.
 *
 * Raw HTML is stripped and unsafe links (javascript:, data:, ...) are
 * removed, so the result can be rendered without further escaping.
 */
final class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return (string) Str::markdown($markdown, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
        ]);
    }

    /**
     * Estimate the reading time of the text in whole minutes.
     */
    public static function readingTime(?string $markdown, int $wordsPerMinute = 200): int
    {
        $words = str_word_count(strip_tags((string) $markdown));

        return max(1, (int) ceil($words / $wordsPerMinute));
    }
}
