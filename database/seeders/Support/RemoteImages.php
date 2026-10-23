<?php

declare(strict_types = 1);

namespace Database\Seeders\Support;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Throwable;

/**
 * Downloads demo images once and keeps them in a local cache so that
 * re-seeding works offline. Falls back to a generated placeholder.
 */
final class RemoteImages
{
    private const array PALETTE = ['#e0e7ff', '#fce7f3', '#dcfce7', '#fef3c7', '#e0f2fe', '#f3e8ff', '#ffe4e6', '#ccfbf1'];

    private readonly string $cacheDirectory;

    private bool $online = true;

    public function __construct(private readonly ImageManagerInterface $images)
    {
        $this->cacheDirectory = storage_path('app/seed-cache');

        File::ensureDirectoryExists($this->cacheDirectory);
    }

    /**
     * Fetch the given URLs, keyed like the input, returning binary image data.
     *
     * @param array<array-key, string> $urls
     * @param positive-int             $concurrency
     *
     * @return array<array-key, string>
     */
    public function fetchMany(array $urls, int $concurrency = 12): array
    {
        $results = [];
        $missing = [];

        foreach ($urls as $key => $url) {
            $cached = $this->cachePath($url);

            if (is_file($cached)) {
                $results[$key] = (string) file_get_contents($cached);
            } else {
                $missing[$key] = $url;
            }
        }

        foreach (array_chunk($missing, $concurrency, preserve_keys: true) as $batch) {
            foreach ($this->download($batch) as $key => $binary) {
                $results[$key] = $binary;
            }
        }

        return $results;
    }

    public function fetch(string $url): string
    {
        return $this->fetchMany([$url])[0];
    }

    /**
     * @param array<array-key, string> $batch
     *
     * @return array<array-key, string>
     */
    private function download(array $batch): array
    {
        $responses = [];

        if ($this->online) {
            try {
                $responses = Http::pool(static function (Pool $pool) use ($batch): array {
                    $requests = [];

                    foreach ($batch as $key => $url) {
                        $requests[] = $pool->as((string) $key)->timeout(20)->connectTimeout(5)->retry(2, 250, throw: false)->get($url);
                    }

                    return $requests;
                });
            } catch (Throwable) {
                $this->online = false;
            }
        }

        $results = [];

        foreach ($batch as $key => $url) {
            $response = $responses[(string) $key] ?? null;

            if ($response instanceof Response && $response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                file_put_contents($this->cachePath($url), $response->body());
                $results[$key] = $response->body();

                continue;
            }

            $results[$key] = $this->placeholder($url);
        }

        return $results;
    }

    private function placeholder(string $seed): string
    {
        $color = self::PALETTE[crc32($seed) % count(self::PALETTE)];

        return $this->images->createImage(800, 800)->fill($color)->encode(new PngEncoder)->toString();
    }

    private function cachePath(string $url): string
    {
        return $this->cacheDirectory . '/' . sha1($url) . '.img';
    }
}
