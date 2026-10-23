import { Form, Head, router } from '@inertiajs/react';
import { HelpCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { DataCard } from '@/components/admin/data-card';
import { Field, SwitchField } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { EmptyState } from '@/components/shop/empty-state';
import { StatusBadge } from '@/components/shop/status-badge';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Faq } from '@/types';

export default function Faqs({ faqs }: { faqs: Faq[] }) {
    const [editing, setEditing] = useState<Faq | 'new' | null>(null);

    const topics = useMemo(
        () => [...new Set(faqs.map((faq) => faq.topic))],
        [faqs],
    );
    const grouped = useMemo(
        () =>
            topics.map((topic) => ({
                topic,
                items: faqs.filter((faq) => faq.topic === topic),
            })),
        [topics, faqs],
    );

    return (
        <>
            <Head title="FAQs" />
            <PageHeader
                title="FAQs"
                description="Answers shown on the public help page, grouped by topic."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus />
                        New question
                    </Button>
                }
            />

            {faqs.length === 0 ? (
                <EmptyState icon={HelpCircle} title="No questions yet" />
            ) : (
                grouped.map((group) => (
                    <DataCard
                        key={group.topic}
                        title={group.topic}
                        description={`${group.items.length} questions`}
                        flush
                    >
                        <ul className="divide-y">
                            {group.items.map((faq) => (
                                <li
                                    key={faq.id}
                                    className="flex items-start gap-4 px-5 py-4"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {faq.question}
                                        </p>
                                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                            {faq.answer}
                                        </p>
                                    </div>
                                    {!faq.is_published && (
                                        <StatusBadge tone="neutral">
                                            Hidden
                                        </StatusBadge>
                                    )}
                                    <div className="flex shrink-0 gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Edit question"
                                            onClick={() => setEditing(faq)}
                                        >
                                            <Pencil />
                                        </Button>
                                        <ConfirmButton
                                            title="Delete this question?"
                                            description={faq.question}
                                            onConfirm={() =>
                                                router.delete(
                                                    admin.faqs.destroy(faq.id),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Delete question"
                                                className="text-destructive"
                                            >
                                                <Trash2 />
                                            </Button>
                                        </ConfirmButton>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </DataCard>
                ))
            )}

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="sm:max-w-xl">
                    {editing !== null && (
                        <FaqForm
                            key={editing === 'new' ? 'new' : editing.id}
                            faq={editing === 'new' ? null : editing}
                            topics={topics}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function FaqForm({
    faq,
    topics,
    onDone,
}: {
    faq: Faq | null;
    topics: string[];
    onDone: () => void;
}) {
    const action = faq
        ? admin.faqs.update.form(faq.id)
        : admin.faqs.store.form();

    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="space-y-5"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            {faq ? 'Edit question' : 'New question'}
                        </DialogTitle>
                        <DialogDescription>
                            Pick an existing topic or type a new one.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                        <Field label="Topic" error={errors.topic}>
                            {(id) => (
                                <>
                                    <Input
                                        id={id}
                                        name="topic"
                                        list="faq-topics"
                                        defaultValue={faq?.topic}
                                        required
                                        maxLength={60}
                                    />
                                    <datalist id="faq-topics">
                                        {topics.map((topic) => (
                                            <option key={topic} value={topic} />
                                        ))}
                                    </datalist>
                                </>
                            )}
                        </Field>
                        <Field label="Order" error={errors.position}>
                            {(id) => (
                                <Input
                                    id={id}
                                    name="position"
                                    type="number"
                                    min="0"
                                    defaultValue={faq?.position ?? 0}
                                />
                            )}
                        </Field>
                    </div>
                    <Field label="Question" error={errors.question}>
                        {(id) => (
                            <Input
                                id={id}
                                name="question"
                                defaultValue={faq?.question}
                                required
                                maxLength={255}
                            />
                        )}
                    </Field>
                    <Field label="Answer" error={errors.answer}>
                        {(id) => (
                            <Textarea
                                id={id}
                                name="answer"
                                defaultValue={faq?.answer}
                                rows={5}
                                required
                                maxLength={5000}
                            />
                        )}
                    </Field>
                    <SwitchField
                        name="is_published"
                        label="Published"
                        defaultChecked={faq?.is_published ?? true}
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onDone}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save question
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

Faqs.layout = withAdminLayout({ title: 'FAQs', href: admin.faqs.index() });
