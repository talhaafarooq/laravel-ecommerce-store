import { ImageOff } from 'lucide-react';
import { cn } from '@/lib/utils';

export function ProductImage({
    src,
    alt,
    className,
    imgClassName,
    loading = 'lazy',
}: {
    src: string | null;
    alt: string;
    className?: string;
    imgClassName?: string;
    loading?: 'lazy' | 'eager';
}) {
    return (
        <div
            className={cn(
                'relative flex items-center justify-center overflow-hidden bg-muted/60',
                className,
            )}
        >
            {src ? (
                <img
                    src={src}
                    alt={alt}
                    loading={loading}
                    decoding="async"
                    className={cn('size-full object-contain', imgClassName)}
                />
            ) : (
                <ImageOff
                    className="size-8 text-muted-foreground/50"
                    aria-hidden
                />
            )}
        </div>
    );
}
