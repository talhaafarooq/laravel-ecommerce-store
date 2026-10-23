import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

/** Label + control + validation message, wiring the control id for you. */
export function Field({
    label,
    error,
    hint,
    className,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    className?: string;
    children: (id: string) => React.ReactNode;
}) {
    const id = useId();

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{label}</Label>
            {children(id)}
            {hint && !error && (
                <p className="text-xs text-muted-foreground">{hint}</p>
            )}
            <InputError message={error} />
        </div>
    );
}

export function FormSection({
    title,
    description,
    children,
    className,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <section className={cn('rounded-xl border bg-card', className)}>
            <header className="border-b px-5 py-4">
                <h2 className="font-semibold">{title}</h2>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </header>
            <div className="grid gap-5 p-5">{children}</div>
        </section>
    );
}

/**
 * A switch that always submits an explicit `1` or `0` with the surrounding form,
 * because an unchecked native switch would otherwise send nothing at all.
 */
export function SwitchField({
    name,
    label,
    description,
    defaultChecked = false,
}: {
    name: string;
    label: string;
    description?: string;
    defaultChecked?: boolean;
}) {
    const id = useId();
    const [checked, setChecked] = useState(defaultChecked);

    return (
        <div className="flex items-start justify-between gap-4">
            <div className="grid gap-0.5">
                <Label htmlFor={id}>{label}</Label>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <input type="hidden" name={name} value={checked ? '1' : '0'} />
            <Switch id={id} checked={checked} onCheckedChange={setChecked} />
        </div>
    );
}
