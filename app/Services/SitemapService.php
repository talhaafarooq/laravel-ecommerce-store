<?php

declare(strict_types = 1);

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Builder;
use XMLWriter;

/**
 * Builds the XML sitemap submitted to search engines.
 */
final readonly class SitemapService
{
    public function __construct(private Cache $cache) {}

    public function xml(): string
    {
        return $this->cache->remember('sitemap.xml', 3600, fn (): string => $this->build());
    }

    private function build(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach (['home', 'shop.index', 'blog.index', 'about', 'faq', 'contact'] as $route) {
            $this->url($xml, route($route), null, $route === 'home' ? '1.0' : '0.6');
        }

        Category::query()->active()->select(['id', 'slug', 'updated_at'])->lazyById(500)
            ->each(fn (Category $category) => $this->url($xml, route('categories.show', $category), $category->updated_at?->toAtomString(), '0.8'));

        Product::query()
            ->where('is_active', true)
            ->whereHas('category', static fn (Builder $query) => $query->where('is_active', true))
            ->select(['id', 'slug', 'updated_at'])
            ->lazyById(500)
            ->each(fn (Product $product) => $this->url($xml, route('products.show', $product), $product->updated_at?->toAtomString(), '0.9'));

        Post::query()->published()->select(['id', 'slug', 'updated_at'])->lazyById(500)
            ->each(fn (Post $post) => $this->url($xml, route('blog.show', $post), $post->updated_at?->toAtomString(), '0.5'));

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function url(XMLWriter $xml, string $location, ?string $lastModified, string $priority): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);

        if ($lastModified !== null) {
            $xml->writeElement('lastmod', $lastModified);
        }

        $xml->writeElement('priority', $priority);
        $xml->endElement();
    }
}
