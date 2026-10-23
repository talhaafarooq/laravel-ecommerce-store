import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const EMPTY = '__empty__';

/**
 * A select that submits with the surrounding native form through a hidden input.
 * Pass `emptyLabel` to offer an explicit "none" choice that submits an empty value.
 */
export function SelectField({
    id,
    name,
    options,
    defaultValue = null,
    emptyLabel,
    placeholder,
    required = false,
    onChange,
}: {
    id?: string;
    name: string;
    options: { value: string; label: string }[];
    defaultValue?: string | null;
    emptyLabel?: string;
    placeholder?: string;
    required?: boolean;
    onChange?: (value: string) => void;
}) {
    const [selected, setSelected] = useState(
        defaultValue ?? (emptyLabel ? EMPTY : ''),
    );

    return (
        <>
            <input
                type="hidden"
                name={name}
                value={selected === EMPTY ? '' : selected}
            />
            <Select
                value={selected}
                onValueChange={(next) => {
                    setSelected(next);
                    onChange?.(next === EMPTY ? '' : next);
                }}
                required={required}
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {emptyLabel && (
                        <SelectItem value={EMPTY}>{emptyLabel}</SelectItem>
                    )}
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
