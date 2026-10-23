import { router, useHttp } from '@inertiajs/react';
import { Loader2, Search, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { Price } from '@/components/shop/price';
import { ProductImage } from '@/components/shop/product-image';
import { cn } from '@/lib/utils';
import { show } from '@/routes/products';
import { suggestions as suggestionsRoute } from '@/routes/search';
import { index as shopIndex } from '@/routes/shop';
import type { ProductCard } from '@/types';

type SuggestionResponse = { products: ProductCard[] };

/**
 * Catalogue search with debounced live suggestions and keyboard navigation.
 */
export function SearchBox({
    className,
    autoFocus = false,
    onNavigate,
}: {
    className?: string;
    autoFocus?: boolean;
    onNavigate?: () => void;
}) {
    const listId = useId();
    const containerRef = useRef<HTMLFormElement>(null);
    const [query, setQuery] = useState(() =>
        typeof window === 'undefined'
            ? ''
            : (new URLSearchParams(window.location.search).get('q') ?? ''),
    );
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(-1);
    const [results, setResults] = useState<ProductCard[]>([]);
    const http = useHttp<{ q: string }, SuggestionResponse>(
        'get',
        suggestionsRoute.url(),
        { q: '' },
    );

    const term = query.trim();

    useEffect(() => {
        if (term.length < 2) {
            return;
        }

        const timer = window.setTimeout(() => {
            http.cancel();
            http.transform(() => ({ q: term }));
            http.get(suggestionsRoute.url())
                .then((response) => {
                    setResults(response.products);
                    setActive(-1);
                    setOpen(true);
                })
                .catch(() => setResults([]));
        }, 250);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [term]);

    useEffect(() => {
        const close = (event: MouseEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', close);

        return () => document.removeEventListener('mousedown', close);
    }, []);

    const visible = open && term.length >= 2;

    const go = (url: string) => {
        setOpen(false);
        onNavigate?.();
        router.visit(url);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (active >= 0 && results[active]) {
            go(show.url(results[active].slug));

            return;
        }

        go(shopIndex.url({ query: term ? { q: term } : {} }));
    };

    const onKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (!visible || results.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((index) => (index + 1) % results.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => (index <= 0 ? results.length - 1 : index - 1));
        } else if (event.key === 'Escape') {
            setOpen(false);
        }
    };

    return (
        <form
            ref={containerRef}
            role="search"
            onSubmit={submit}
            className={cn('relative w-full', className)}
        >
            <label htmlFor={`${listId}-input`} className="sr-only">
                Search products
            </label>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <input
                id={`${listId}-input`}
                type="search"
                value={query}
                autoFocus={autoFocus}
                autoComplete="off"
                placeholder="Search for products, brands and more…"
                onChange={(event) => setQuery(event.target.value)}
                onFocus={() => results.length > 0 && setOpen(true)}
                onKeyDown={onKeyDown}
                role="combobox"
                aria-expanded={visible}
                aria-controls={listId}
                aria-autocomplete="list"
                aria-activedescendant={
                    active >= 0 ? `${listId}-${active}` : undefined
                }
                className="h-10 w-full rounded-full border bg-muted/50 pr-10 pl-9 text-sm transition-colors outline-none placeholder:text-muted-foreground focus:border-ring focus:bg-background focus:ring-[3px] focus:ring-ring/40 [&::-webkit-search-cancel-button]:hidden"
            />
            {http.processing ? (
                <Loader2 className="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-muted-foreground" />
            ) : (
                query && (
                    <button
                        type="button"
                        onClick={() => {
                            setQuery('');
                            setResults([]);
                        }}
                        className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        aria-label="Clear search"
                    >
                        <X className="size-4" />
                    </button>
                )
            )}

            {visible && (
                <div
                    id={listId}
                    role="listbox"
                    className="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-xl border bg-popover shadow-xl"
                >
                    {results.length === 0 ? (
                        <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                            {http.processing
                                ? 'Searching…'
                                : `No products match “${term}”.`}
                        </p>
                    ) : (
                        <ul className="max-h-96 overflow-y-auto py-2">
                            {results.map((product, index) => (
                                <li
                                    key={product.id}
                                    id={`${listId}-${index}`}
                                    role="option"
                                    aria-selected={index === active}
                                >
                                    <button
                                        type="button"
                                        onMouseEnter={() => setActive(index)}
                                        onClick={() =>
                                            go(show.url(product.slug))
                                        }
                                        className={cn(
                                            'flex w-full items-center gap-3 px-4 py-2 text-left',
                                            index === active && 'bg-accent',
                                        )}
                                    >
                                        <ProductImage
                                            src={product.image}
                                            alt=""
                                            className="size-11 shrink-0 rounded-md p-1"
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium">
                                                {product.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {product.category?.name}
                                            </span>
                                        </span>
                                        <Price
                                            price={product.price}
                                            size="sm"
                                        />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <button
                        type="submit"
                        className="flex w-full items-center gap-2 border-t px-4 py-3 text-sm font-medium text-primary hover:bg-accent"
                    >
                        <Search className="size-4" />
                        See all results for “{term}”
                    </button>
                </div>
            )}
        </form>
    );
}
