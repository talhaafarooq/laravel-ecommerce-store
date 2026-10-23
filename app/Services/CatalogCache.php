<?php

declare(strict_types = 1);

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

/**
 * Versioned cache for catalogue data shared across requests.
 *
 * Flushing bumps the version so every cached catalogue entry is ignored at
 * once, which works with any cache store (no tag support required).
 */
final readonly class CatalogCache
{
    private const string VERSION_KEY = 'catalog:version';

    public function __construct(private Cache $cache) {}

    /**
     * @template TValue
     *
     * @param Closure(): TValue $callback
     *
     * @return TValue
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        return $this->cache->remember("catalog:{$this->version()}:{$key}", $ttl, $callback);
    }

    public function flush(): void
    {
        $this->cache->forever(self::VERSION_KEY, (string) Str::ulid());
    }

    private function version(): string
    {
        $version = $this->cache->get(self::VERSION_KEY);

        if (!is_string($version)) {
            $version = (string) Str::ulid();
            $this->cache->forever(self::VERSION_KEY, $version);
        }

        return $version;
    }
}
