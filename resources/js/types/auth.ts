import type { Option } from '@/types/shop';

export type User = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    role: Option<'admin' | 'customer'>;
    is_admin: boolean;
    is_active: boolean;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    last_login_at: string | null;
    created_at: string | null;
    orders_count?: number;
    total_spent?: import('@/types/shop').Money;
};

export type Auth = {
    user: User | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
