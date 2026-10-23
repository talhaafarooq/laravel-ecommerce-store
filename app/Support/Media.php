<?php

declare(strict_types = 1);

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves the disk and public URLs for uploaded media.
 */
final class Media
{
    public static function diskName(): string
    {
        return (string) config('shop.media_disk', 'public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    /**
     * Get the public URL for a stored media path.
     *
     * Absolute URLs are returned untouched so externally hosted media works.
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return self::disk()->url($path);
    }
}
