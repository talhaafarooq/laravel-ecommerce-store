import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** True while an Inertia visit is in flight, e.g. to dim stale content during a refetch. */
export function usePageLoading(): boolean {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setLoading(true));
        const offFinish = router.on('finish', () => setLoading(false));

        return () => {
            offStart();
            offFinish();
        };
    }, []);

    return loading;
}
