import { Form, Head, router } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/shop/empty-state';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { defaultMethod as makeDefault, destroy, store, update } from '@/routes/account/addresses';
import type { Address } from '@/types';

export default function Addresses({ addresses }: { addresses: Address[] }) {
    const [editing, setEditing] = useState<Address | 'new' | null>(null);

    return (
        <>
            <Head title="Addresses" />
            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-xl font-semibold">Saved addresses</h2>
                <Button onClick={() => setEditing('new')}>
                    <Plus />
                    Add address
                </Button>
            </div>

            {addresses.length === 0 ? (
                <EmptyState
                    icon={MapPin}
                    title="No saved addresses"
                    description="Save an address to check out faster next time."
                />
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2">
                    {addresses.map((address) => (
                        <li
                            key={address.id}
                            className={cn(
                                'flex flex-col rounded-xl border bg-card p-5',
                                address.is_default && 'border-primary/50 ring-1 ring-primary/20',
                            )}
                        >
                            <div className="mb-3 flex items-center justify-between">
                                <p className="font-medium">{address.label ?? 'Address'}</p>
                                {address.is_default && (
                                    <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                        <Star className="size-3" />
                                        Default
                                    </span>
                                )}
                            </div>
                            <address className="flex-1 space-y-0.5 text-sm not-italic text-muted-foreground">
                                <p className="text-foreground">{address.full_name}</p>
                                <p>{address.line1}</p>
                                {address.line2 && <p>{address.line2}</p>}
                                <p>
                                    {[address.city, address.state, address.postal_code].filter(Boolean).join(', ')}
                                </p>
                                <p>{address.country}</p>
                                <p>{address.phone}</p>
                            </address>
                            <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                                <Button variant="outline" size="sm" onClick={() => setEditing(address)}>
                                    <Pencil />
                                    Edit
                                </Button>
                                {!address.is_default && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.post(makeDefault(address.id), undefined, { preserveScroll: true })}
                                    >
                                        Make default
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="ml-auto text-destructive"
                                    onClick={() => router.delete(destroy(address.id), { preserveScroll: true })}
                                >
                                    <Trash2 />
                                    <span className="sr-only">Delete</span>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent className="max-h-[90svh] overflow-y-auto sm:max-w-xl">
                    {editing !== null && (
                        <AddressForm
                            key={editing === 'new' ? 'new' : editing.id}
                            address={editing === 'new' ? null : editing}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function AddressForm({ address, onDone }: { address: Address | null; onDone: () => void }) {
    const action = address ? update.form(address.id) : store.form();

    return (
        <Form {...action} options={{ preserveScroll: true }} onSuccess={onDone} className="space-y-4">
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>{address ? 'Edit address' : 'Add a new address'}</DialogTitle>
                        <DialogDescription>Where should we deliver your orders?</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Label (e.g. Home)" error={errors.label}>
                            <Input name="label" defaultValue={address?.label ?? ''} maxLength={40} />
                        </Field>
                        <Field label="Full name" error={errors.full_name}>
                            <Input name="full_name" defaultValue={address?.full_name} required autoComplete="name" />
                        </Field>
                        <Field label="Phone" error={errors.phone} className="sm:col-span-2">
                            <Input name="phone" type="tel" defaultValue={address?.phone} required autoComplete="tel" />
                        </Field>
                        <Field label="Address" error={errors.line1} className="sm:col-span-2">
                            <Input name="line1" defaultValue={address?.line1} required autoComplete="address-line1" />
                        </Field>
                        <Field label="Apartment, suite, etc." error={errors.line2} className="sm:col-span-2">
                            <Input name="line2" defaultValue={address?.line2 ?? ''} autoComplete="address-line2" />
                        </Field>
                        <Field label="City" error={errors.city}>
                            <Input name="city" defaultValue={address?.city} required autoComplete="address-level2" />
                        </Field>
                        <Field label="State / region" error={errors.state}>
                            <Input name="state" defaultValue={address?.state ?? ''} autoComplete="address-level1" />
                        </Field>
                        <Field label="Postal code" error={errors.postal_code}>
                            <Input name="postal_code" defaultValue={address?.postal_code} required autoComplete="postal-code" />
                        </Field>
                        <Field label="Country (2-letter code)" error={errors.country}>
                            <Input name="country" defaultValue={address?.country ?? 'US'} maxLength={2} required className="uppercase" />
                        </Field>
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_default" value="0" />
                        <input
                            type="checkbox"
                            name="is_default"
                            value="1"
                            defaultChecked={address?.is_default ?? false}
                            className="size-4 rounded border-input accent-primary"
                        />
                        Use as my default address
                    </label>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onDone}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save address
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

function Field({
    label,
    error,
    className,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
