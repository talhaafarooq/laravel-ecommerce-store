import { Head, Link, usePage } from '@inertiajs/react';
import { HeartHandshake, Leaf, ShieldCheck, Sparkles } from 'lucide-react';
import { Container } from '@/components/shop/container';
import { Button } from '@/components/ui/button';
import { contact } from '@/routes';
import { index as shopIndex } from '@/routes/shop';

const values = [
    {
        icon: ShieldCheck,
        title: 'Quality you can trust',
        text: 'Every product is vetted by our team and backed by a 30-day return policy.',
    },
    {
        icon: HeartHandshake,
        title: 'People first',
        text: 'Real humans answer your questions, usually within a few hours.',
    },
    {
        icon: Leaf,
        title: 'Responsible choices',
        text: 'We work with suppliers who care about materials, packaging and fair labour.',
    },
    {
        icon: Sparkles,
        title: 'Fair prices',
        text: 'Honest pricing and regular deals so great products stay within reach.',
    },
];

const stats = [
    { value: '180+', label: 'Products' },
    { value: '50+', label: 'Brands' },
    { value: '4.6★', label: 'Average rating' },
    { value: '24h', label: 'Dispatch time' },
];

export default function About() {
    const { name } = usePage().props;

    return (
        <>
            <Head title="About us" />
            <section className="border-b bg-gradient-to-br from-primary/10 via-background to-background">
                <Container className="max-w-3xl py-16 text-center lg:py-24">
                    <h1 className="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        We make shopping simple, fast and a little bit joyful.
                    </h1>
                    <p className="mt-5 text-lg text-muted-foreground">
                        {name} started with a simple idea: buying things online
                        should feel as good as unwrapping them. Today we bring
                        together hundreds of products from brands we love, with
                        the service you would expect from a neighbourhood shop.
                    </p>
                </Container>
            </section>

            <Container className="py-14">
                <dl className="grid grid-cols-2 gap-6 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-xl border bg-card p-6 text-center"
                        >
                            <dt className="text-sm text-muted-foreground">
                                {stat.label}
                            </dt>
                            <dd className="mt-1 text-3xl font-semibold tracking-tight text-primary">
                                {stat.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </Container>

            <Container className="pb-16">
                <h2 className="mb-8 text-center text-2xl font-semibold tracking-tight">
                    What we stand for
                </h2>
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {values.map(({ icon: Icon, title, text }) => (
                        <div
                            key={title}
                            className="rounded-xl border bg-card p-6"
                        >
                            <span className="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Icon className="size-5" />
                            </span>
                            <h3 className="mt-4 font-semibold">{title}</h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {text}
                            </p>
                        </div>
                    ))}
                </div>
            </Container>

            <Container className="pb-4">
                <div className="rounded-2xl bg-primary px-6 py-12 text-center text-primary-foreground">
                    <h2 className="text-2xl font-semibold tracking-tight">
                        Ready to find something you love?
                    </h2>
                    <div className="mt-6 flex flex-wrap justify-center gap-3">
                        <Button variant="secondary" size="lg" asChild>
                            <Link href={shopIndex()}>Start shopping</Link>
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            className="border-primary-foreground/40 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground"
                            asChild
                        >
                            <Link href={contact()}>Talk to us</Link>
                        </Button>
                    </div>
                </div>
            </Container>
        </>
    );
}
