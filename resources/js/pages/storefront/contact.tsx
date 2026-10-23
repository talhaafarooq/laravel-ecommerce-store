import { Form, Head, usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import InputError from '@/components/input-error';
import { Container } from '@/components/shop/container';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/contact';

export default function Contact() {
    const { auth, store: shop } = usePage().props;

    return (
        <>
            <Head title="Contact us" />
            <div className="border-b bg-muted/40">
                <Container className="py-12 text-center">
                    <h1 className="text-4xl font-semibold tracking-tight">
                        Get in touch
                    </h1>
                    <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
                        Questions about an order, a product or anything else?
                        Send us a message.
                    </p>
                </Container>
            </div>

            <Container className="grid gap-10 py-12 lg:grid-cols-[1fr_22rem]">
                <Form
                    {...store.form()}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="space-y-5 rounded-xl border bg-card p-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {/* Honeypot: hidden from people, tempting to bots. */}
                            <div
                                className="absolute -left-[9999px]"
                                aria-hidden
                            >
                                <label htmlFor="website">Website</label>
                                <input
                                    id="website"
                                    name="website"
                                    tabIndex={-1}
                                    autoComplete="off"
                                />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={auth.user?.name}
                                        autoComplete="name"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        defaultValue={auth.user?.email}
                                        autoComplete="email"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">
                                        Phone (optional)
                                    </Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        defaultValue={auth.user?.phone ?? ''}
                                        autoComplete="tel"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        name="subject"
                                        required
                                    />
                                    <InputError message={errors.subject} />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="message">Message</Label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    rows={6}
                                    required
                                    minLength={10}
                                />
                                <InputError message={errors.message} />
                            </div>
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Send message
                            </Button>
                        </>
                    )}
                </Form>

                <aside className="space-y-6">
                    <div className="space-y-5 rounded-xl border bg-card p-6">
                        <h2 className="font-semibold">Contact details</h2>
                        <ul className="space-y-4 text-sm">
                            <li className="flex gap-3">
                                <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                                <span>{shop.address}</span>
                            </li>
                            <li className="flex gap-3">
                                <Phone className="size-4 shrink-0 text-primary" />
                                <a
                                    href={`tel:${shop.support_phone}`}
                                    className="hover:underline"
                                >
                                    {shop.support_phone}
                                </a>
                            </li>
                            <li className="flex gap-3">
                                <Mail className="size-4 shrink-0 text-primary" />
                                <a
                                    href={`mailto:${shop.support_email}`}
                                    className="hover:underline"
                                >
                                    {shop.support_email}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div className="rounded-xl border bg-card p-6 text-sm">
                        <h2 className="mb-2 font-semibold">Support hours</h2>
                        <p className="text-muted-foreground">
                            Monday – Friday, 9am – 6pm
                        </p>
                        <p className="text-muted-foreground">
                            Saturday, 10am – 2pm
                        </p>
                    </div>
                </aside>
            </Container>
        </>
    );
}
