import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Mail, Phone, Reply, Trash2 } from 'lucide-react';
import { ConfirmButton } from '@/components/admin/confirm-button';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { ContactMessage } from '@/types';

export default function ShowMessage({ message }: { message: ContactMessage }) {
    return (
        <>
            <Head title={message.subject} />

            <Link
                href={admin.messages.index()}
                className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft className="size-4" />
                Inbox
            </Link>

            <PageHeader
                title={message.subject}
                description={`Received ${formatDateTime(message.created_at)}`}
                actions={
                    <>
                        <Button asChild>
                            <a
                                href={`mailto:${message.email}?subject=${encodeURIComponent(`Re: ${message.subject}`)}`}
                            >
                                <Reply />
                                Reply by email
                            </a>
                        </Button>
                        <ConfirmButton
                            title="Delete this message?"
                            description="This cannot be undone."
                            onConfirm={() =>
                                router.delete(
                                    admin.messages.destroy(message.id),
                                )
                            }
                        >
                            <Button
                                variant="outline"
                                className="text-destructive"
                            >
                                <Trash2 />
                                Delete
                            </Button>
                        </ConfirmButton>
                    </>
                }
            />

            <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <article className="rounded-xl border bg-card p-6">
                    <p className="text-sm leading-7 whitespace-pre-line">
                        {message.message}
                    </p>
                </article>
                <aside className="h-fit space-y-4 rounded-xl border bg-card p-5 text-sm">
                    <div>
                        <p className="text-xs text-muted-foreground">From</p>
                        <p className="font-medium">{message.name}</p>
                    </div>
                    <p className="flex items-center gap-2">
                        <Mail className="size-4 text-muted-foreground" />
                        <a
                            href={`mailto:${message.email}`}
                            className="truncate hover:underline"
                        >
                            {message.email}
                        </a>
                    </p>
                    {message.phone && (
                        <p className="flex items-center gap-2">
                            <Phone className="size-4 text-muted-foreground" />
                            <a
                                href={`tel:${message.phone}`}
                                className="hover:underline"
                            >
                                {message.phone}
                            </a>
                        </p>
                    )}
                    {message.ip_address && (
                        <p className="text-xs text-muted-foreground">
                            IP {message.ip_address}
                        </p>
                    )}
                </aside>
            </div>
        </>
    );
}

ShowMessage.layout = withAdminLayout({
    title: 'Messages',
    href: admin.messages.index(),
});
