export type Money = {
    amount: number;
    decimal: string;
    formatted: string;
    currency: string;
};

export type Tone =
    | 'neutral'
    | 'info'
    | 'primary'
    | 'success'
    | 'warning'
    | 'danger';

export type Option<T extends string = string> = {
    value: T;
    label: string;
};

export type ToneOption<T extends string = string> = Option<T> & {
    tone: Tone;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type CategorySummary = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    parent_id: number | null;
    is_active: boolean;
    position: number;
    meta_title: string | null;
    meta_description: string | null;
    products_count?: number;
    children_count?: number;
    parent?: { id: number; name: string; slug: string } | null;
    children?: CategorySummary[];
};

export type CategoryOption = {
    id: number;
    parent_id: number | null;
    name: string;
    is_active: boolean;
    parent: { id: number; name: string } | null;
};

export type Brand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    products_count?: number;
};

export type ProductImage = {
    id: number;
    url: string | null;
    thumbnail: string | null;
    alt: string | null;
    position: number;
};

export type ProductCard = {
    id: number;
    name: string;
    slug: string;
    sku: string;
    short_description: string | null;
    price: Money;
    compare_at_price: Money | null;
    is_on_sale: boolean;
    discount_percentage: number | null;
    rating: number;
    reviews_count: number;
    in_stock: boolean;
    stock_status: ToneOption;
    is_featured: boolean;
    image: string | null;
    brand: { name: string; slug: string } | null;
    category: { name: string; slug: string } | null;
};

/** The lean shape returned by the search typeahead. */
export type ProductSuggestion = Pick<ProductCard, 'id' | 'name' | 'slug' | 'price' | 'image' | 'category'>;

export type Product = ProductCard & {
    description: string | null;
    description_html: string;
    stock_quantity: number;
    max_quantity: number;
    images: ProductImage[];
    category: {
        id: number;
        name: string;
        slug: string;
        parent: { name: string; slug: string } | null;
    };
    meta_title: string;
    meta_description: string | null;
    created_at: string | null;
};

export type AdminProduct = {
    id: number;
    name: string;
    slug: string;
    sku: string;
    category_id: number;
    brand_id: number | null;
    category?: { id: number; name: string };
    brand?: { id: number; name: string } | null;
    short_description: string | null;
    description: string | null;
    price: Money;
    compare_at_price: Money | null;
    stock_quantity: number;
    low_stock_threshold: number;
    stock_status: ToneOption;
    is_active: boolean;
    is_featured: boolean;
    sales_count: number;
    rating: number;
    reviews_count: number;
    image: string | null;
    images?: ProductImage[];
    meta_title: string | null;
    meta_description: string | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
};

export type Review = {
    id: number;
    rating: number;
    title: string | null;
    body: string;
    is_verified_purchase: boolean;
    is_approved: boolean;
    created_at: string | null;
    author: { id: number | null; name: string; avatar: string | null };
    product?: { id: number; name: string; slug: string; trashed: boolean };
    can_delete: boolean;
};

export type CartLine = {
    id: number;
    product: {
        id: number;
        name: string;
        slug: string;
        sku: string;
        image: string | null;
        category: string | null;
        stock_status: ToneOption;
    };
    quantity: number;
    unit_price: Money;
    line_total: Money;
    max_quantity: number;
    issue: string | null;
};

export type OrderTotals = {
    subtotal: Money;
    discount: Money;
    shipping: Money;
    tax: Money;
    total: Money;
    free_shipping_remaining: Money | null;
};

export type Cart = {
    items: CartLine[];
    totals: OrderTotals;
    coupon: { code: string; summary: string; error: string | null } | null;
    item_count: number;
    has_issues: boolean;
    is_empty: boolean;
};

export type Address = {
    id: number;
    label: string | null;
    full_name: string;
    phone: string;
    line1: string;
    line2: string | null;
    city: string;
    state: string | null;
    postal_code: string;
    country: string;
    is_default: boolean;
};

export type OrderItem = {
    id: number;
    product_id: number | null;
    product_slug: string | null;
    name: string;
    sku: string;
    image: string | null;
    unit_price: Money;
    quantity: number;
    line_total: Money;
};

export type OrderTimelineEntry = {
    id: number;
    from: Option | null;
    to: ToneOption;
    note: string | null;
    actor: string | null;
    created_at: string | null;
};

export type Payment = {
    id: number;
    gateway: Option;
    status: Option;
    amount: Money;
    reference?: string | null;
    failure_reason: string | null;
    paid_at: string | null;
    created_at: string | null;
};

export type Order = {
    id: number;
    number: string;
    status: ToneOption & { step: number };
    payment_status: ToneOption;
    payment_method: Option;
    currency: string;
    subtotal: Money;
    discount_total: Money;
    shipping_total: Money;
    tax_total: Money;
    grand_total: Money;
    coupon_code: string | null;
    customer_email: string;
    notes: string | null;
    shipping_address: {
        name: string;
        phone: string;
        line1: string;
        line2: string | null;
        city: string;
        state: string | null;
        postal_code: string;
        country: string;
        lines: string[];
    };
    items_count?: number;
    items?: OrderItem[];
    timeline?: OrderTimelineEntry[];
    payments?: Payment[];
    customer?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    } | null;
    cancellation_reason: string | null;
    placed_at: string;
    paid_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    cancelled_at: string | null;
    allowed_transitions: Option[];
    can: { cancel: boolean; pay: boolean };
};

export type Post = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    cover: string | null;
    published_at: string | null;
    is_published: boolean;
    reading_time: number;
    meta_title: string;
    meta_description: string | null;
    author?: { name: string; avatar: string | null } | null;
    body_html?: string;
    body?: string;
};

export type Faq = {
    id: number;
    topic: string;
    question: string;
    answer: string;
    position: number;
    is_published: boolean;
};

export type Coupon = {
    id: number;
    code: string;
    description: string | null;
    type: Option<'percentage' | 'fixed'>;
    value: string;
    summary: string;
    max_discount: Money | null;
    min_subtotal: Money | null;
    usage_limit: number | null;
    usage_limit_per_user: number | null;
    times_used: number;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
    state: { label: string; tone: Tone };
};

export type StockMovement = {
    id: number;
    type: Option;
    quantity: number;
    balance_after: number;
    note: string | null;
    product?: { id: number; name: string; sku: string };
    user: string | null;
    order_number: string | null;
    created_at: string | null;
};

export type ContactMessage = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string;
    message: string;
    ip_address: string | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string | null;
};

export type StoreDetails = {
    currency: string;
    support_email: string;
    support_phone: string;
    address: string;
    social: Record<'facebook' | 'instagram' | 'x' | 'youtube', string>;
    free_shipping_threshold: Money | null;
};

export type ProductFilters = {
    search: string | null;
    brands: string[];
    min_price: string | null;
    max_price: string | null;
    min_rating: number | null;
    in_stock: boolean;
    on_sale: boolean;
    sort: string;
};
