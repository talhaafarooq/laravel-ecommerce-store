import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/** Search field that reports its value after the user pauses typing. */
export function SearchInput({
    value,
    onSearch,
    placeholder = 'Search…',
    delay = 350,
    className,
}: {
    value: string | null;
    onSearch: (value: string) => void;
    placeholder?: string;
    delay?: number;
    className?: string;
}) {
    const [text, setText] = useState(value ?? '');
    const last = useRef(value ?? '');

    useEffect(() => {
        if (text === last.current) {
            return;
        }

        const timer = window.setTimeout(() => {
            last.current = text;
            onSearch(text.trim());
        }, delay);

        return () => window.clearTimeout(timer);
    }, [text, delay, onSearch]);

    return (
        <div className={cn('relative', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                type="search"
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className="pl-9"
            />
        </div>
    );
}
