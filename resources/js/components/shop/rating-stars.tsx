import { Star } from 'lucide-react';
import { cn } from '@/lib/utils';

export function RatingStars({
    rating,
    count,
    size = 'sm',
    className,
}: {
    rating: number;
    count?: number;
    size?: 'sm' | 'md';
    className?: string;
}) {
    const iconSize = size === 'sm' ? 'size-3.5' : 'size-5';

    return (
        <div className={cn('flex items-center gap-1.5', className)}>
            <div
                className="flex items-center gap-0.5"
                role="img"
                aria-label={`Rated ${rating.toFixed(1)} out of 5`}
            >
                {[1, 2, 3, 4, 5].map((star) => {
                    const fill = Math.max(0, Math.min(1, rating - star + 1));

                    return (
                        <span key={star} className={cn('relative', iconSize)}>
                            <Star
                                className={cn(
                                    'absolute inset-0 text-muted-foreground/30',
                                    iconSize,
                                )}
                                fill="currentColor"
                                strokeWidth={0}
                            />
                            <span
                                className="absolute inset-0 overflow-hidden"
                                style={{ width: `${fill * 100}%` }}
                            >
                                <Star
                                    className={cn('text-amber-400', iconSize)}
                                    fill="currentColor"
                                    strokeWidth={0}
                                />
                            </span>
                        </span>
                    );
                })}
            </div>
            {count !== undefined && (
                <span className="text-xs text-muted-foreground">({count})</span>
            )}
        </div>
    );
}

export function RatingInput({
    value,
    onChange,
    name = 'rating',
}: {
    value: number;
    onChange: (value: number) => void;
    name?: string;
}) {
    return (
        <div className="flex items-center gap-1" role="radiogroup">
            {[1, 2, 3, 4, 5].map((star) => (
                <button
                    key={star}
                    type="button"
                    role="radio"
                    aria-checked={value === star}
                    aria-label={`${star} star${star > 1 ? 's' : ''}`}
                    onClick={() => onChange(star)}
                    className="rounded transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Star
                        className={cn(
                            'size-7',
                            star <= value
                                ? 'text-amber-400'
                                : 'text-muted-foreground/30',
                        )}
                        fill="currentColor"
                        strokeWidth={0}
                    />
                </button>
            ))}
            <input type="hidden" name={name} value={value || ''} />
        </div>
    );
}
