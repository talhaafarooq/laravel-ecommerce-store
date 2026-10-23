import { router, usePage } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { toggle } from '@/routes/account/wishlist';

export function WishlistButton({
    productId,
    slug,
    className,
    withLabel = false,
}: {
    productId: number;
    slug: string;
    className?: string;
    withLabel?: boolean;
}) {
    const { auth, wishlistIds } = usePage().props;
    const [pending, setPending] = useState(false);
    const saved = wishlistIds.includes(productId);

    const onClick = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();

        if (!auth.user) {
            router.visit(login());

            return;
        }

        router.post(toggle(slug), undefined, {
            preserveScroll: true,
            only: ['wishlistIds', 'flash', 'products'],
            onStart: () => setPending(true),
            onFinish: () => setPending(false),
        });
    };

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={pending}
            aria-pressed={saved}
            aria-label={saved ? 'Remove from wishlist' : 'Save to wishlist'}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-full transition-colors disabled:opacity-60',
                withLabel
                    ? 'h-10 border px-4 text-sm font-medium hover:bg-accent'
                    : 'size-9 bg-background/90 shadow-sm ring-1 ring-border backdrop-blur hover:bg-background',
                className,
            )}
        >
            <Heart
                className={cn(
                    'size-4 transition-colors',
                    saved ? 'fill-rose-500 text-rose-500' : 'text-foreground',
                )}
            />
            {withLabel && (saved ? 'Saved' : 'Save to wishlist')}
        </button>
    );
}
