import { useSyncExternalStore } from 'react';

const subscribe = () => () => {};

/**
 * False while rendering on the server and during hydration, true afterwards.
 * Lets browser-only widgets (e.g. charts that measure their container) skip SSR.
 */
export function useIsClient(): boolean {
    return useSyncExternalStore(
        subscribe,
        () => true,
        () => false,
    );
}
