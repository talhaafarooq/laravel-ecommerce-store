import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Search } from 'lucide-react';
import { Container } from '@/components/shop/container';
import { Button } from '@/components/ui/button';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Input } from '@/components/ui/input';
import { contact } from '@/routes';
import type { Faq } from '@/types';

type Topic = { topic: string; items: Faq[] };

export default function FaqPage({ topics }: { topics: Topic[] }) {
    const [query, setQuery] = useState('');

    const visible = useMemo(() => {
        const term = query.trim().toLowerCase();

        if (!term) {
            return topics;
        }

        return topics
            .map((group) => ({
                ...group,
                items: group.items.filter(
                    (faq) =>
                        faq.question.toLowerCase().includes(term) ||
                        faq.answer.toLowerCase().includes(term),
                ),
            }))
            .filter((group) => group.items.length > 0);
    }, [topics, query]);

    return (
        <>
            <Head title="Frequently asked questions" />
            <div className="border-b bg-muted/40">
                <Container className="py-12 text-center">
                    <h1 className="text-4xl font-semibold tracking-tight">
                        How can we help?
                    </h1>
                    <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
                        Answers to the questions we hear most about orders,
                        payments, shipping and returns.
                    </p>
                    <div className="relative mx-auto mt-6 max-w-md">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search the FAQ…"
                            aria-label="Search the FAQ"
                            className="rounded-full pl-9"
                        />
                    </div>
                </Container>
            </div>

            <Container className="max-w-3xl space-y-10 py-12">
                {visible.length === 0 && (
                    <p className="text-center text-muted-foreground">
                        No questions match “{query}”.
                    </p>
                )}
                {visible.map((group) => (
                    <section key={group.topic}>
                        <h2 className="mb-2 text-lg font-semibold">
                            {group.topic}
                        </h2>
                        <Accordion
                            type="multiple"
                            className="rounded-xl border px-5"
                        >
                            {group.items.map((faq) => (
                                <AccordionItem
                                    key={faq.id}
                                    value={String(faq.id)}
                                >
                                    <AccordionTrigger>
                                        {faq.question}
                                    </AccordionTrigger>
                                    <AccordionContent className="text-muted-foreground">
                                        {faq.answer}
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </section>
                ))}

                <div className="rounded-xl border bg-primary/5 p-6 text-center">
                    <h2 className="font-semibold">Still need help?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Our team usually replies within one business day.
                    </p>
                    <Button className="mt-4" asChild>
                        <Link href={contact()}>Contact us</Link>
                    </Button>
                </div>
            </Container>
        </>
    );
}
