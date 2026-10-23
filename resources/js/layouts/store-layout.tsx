import { StoreFooter } from '@/components/shop/store-footer';
import { StoreHeader } from '@/components/shop/store-header';

export default function StoreLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-svh flex-col">
            <a
                href="#main"
                className="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-2 focus:left-2"
            >
                Skip to content
            </a>
            <StoreHeader />
            <main id="main" className="flex-1">
                {children}
            </main>
            <StoreFooter />
        </div>
    );
}
