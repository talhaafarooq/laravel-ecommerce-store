import { router } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/cart/items';

export function AddToCartButton({
    productId,
    quantity = 1,
    disabled = false,
    size = 'default',
    className,
    label = 'Add to cart',
}: {
    productId: number;
    quantity?: number;
    disabled?: boolean;
    size?: 'default' | 'sm' | 'lg';
    className?: string;
    label?: string;
}) {
    const [pending, setPending] = useState(false);

    const add = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();

        router.post(
            store(),
            { product_id: productId, quantity },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['cartCount', 'flash', 'errors'],
                onStart: () => setPending(true),
                onFinish: () => setPending(false),
            },
        );
    };

    return (
        <Button
            type="button"
            size={size}
            className={className}
            disabled={disabled || pending}
            onClick={add}
        >
            {pending ? <Spinner /> : <ShoppingBag />}
            {disabled ? 'Out of stock' : label}
        </Button>
    );
}
