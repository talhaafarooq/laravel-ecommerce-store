<?php

declare(strict_types = 1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rules\File;

/**
 * Upload rules for raster images processed by the image service.
 */
trait ImageRules
{
    protected function imageRule(?int $maxKilobytes = null): File
    {
        return File::image()
            ->types(['jpg', 'jpeg', 'png', 'webp'])
            ->max($maxKilobytes ?? (int) config('shop.images.max_upload_kb', 4096));
    }
}
