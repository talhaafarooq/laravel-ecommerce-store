import type { Auth } from '@/types/auth';
import type { CategorySummary, StoreDetails } from '@/types/shop';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            store: StoreDetails;
            navigation: CategorySummary[];
            cartCount: number;
            wishlistIds: number[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
