import { router } from '@inertiajs/react';
import { useCallback } from 'react';

type Scalar = string | number | boolean | null | undefined;

/**
 * Applies list filters by re-visiting the same URL with a clean query string.
 *
 * Empty, null and false values are dropped and `true` becomes `1`, so the URL
 * only ever contains filters that are actually in effect.
 */
export function useFilters<T extends Record<string, Scalar>>(
    url: string,
    current: T,
    only?: string[],
) {
    return useCallback(
        (changes: Partial<Record<keyof T, Scalar>>) => {
            const merged: Record<string, Scalar> = { ...current, ...changes };

            const query = Object.fromEntries(
                Object.entries(merged)
                    .filter(
                        ([, value]) =>
                            value !== null &&
                            value !== undefined &&
                            value !== '' &&
                            value !== false,
                    )
                    .map(([key, value]) => [key, value === true ? 1 : value]),
            );

            router.get(url, query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
            });
        },
        [url, current, only],
    );
}
