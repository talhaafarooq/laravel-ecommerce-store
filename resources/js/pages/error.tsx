import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Home } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Access denied',
        description: 'You do not have permission to view this page.',
    },
    404: {
        title: 'Page not found',
        description:
            'The page you are looking for may have moved or no longer exists.',
    },
    429: {
        title: 'Slow down a little',
        description:
            'You have made too many requests. Please wait a moment and try again.',
    },
    500: {
        title: 'Something went wrong',
        description:
            'An unexpected error occurred on our side. Our team has been notified.',
    },
    503: {
        title: 'Back soon',
        description:
            'We are performing scheduled maintenance. Please check back shortly.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const message = messages[status] ?? messages[500];

    return (
        <>
            <Head title={message.title} />
            <div className="flex min-h-svh flex-col items-center justify-center bg-gradient-to-b from-primary/5 to-background px-6 text-center">
                <p className="bg-gradient-to-br from-primary to-primary/40 bg-clip-text text-8xl font-bold tracking-tighter text-transparent sm:text-9xl">
                    {status}
                </p>
                <h1 className="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {message.title}
                </h1>
                <p className="mt-2 max-w-md text-muted-foreground">
                    {message.description}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeft />
                        Go back
                    </Button>
                    <Button asChild>
                        <Link href={home()}>
                            <Home />
                            Back to the store
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
