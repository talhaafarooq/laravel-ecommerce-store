<?php

declare(strict_types = 1);

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | All monetary values are stored as integers in the currency's minor unit
    | (e.g. cents). The store operates in a single, two-decimal currency.
    |
    */

    'currency' => [
        'code'   => env('SHOP_CURRENCY', 'USD'),
        'locale' => env('SHOP_CURRENCY_LOCALE', 'en_US'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Contact Details
    |--------------------------------------------------------------------------
    */

    'contact' => [
        'admin_email'   => env('SHOP_ADMIN_EMAIL', 'admin@example.com'),
        'support_email' => env('SHOP_SUPPORT_EMAIL', 'support@example.com'),
        'support_phone' => env('SHOP_SUPPORT_PHONE', '+1 (555) 010-2030'),
        'address'       => env('SHOP_ADDRESS', '221B Market Street, Suite 400, San Francisco, CA 94103'),
    ],

    'social' => [
        'facebook'  => env('SHOP_SOCIAL_FACEBOOK', 'https://facebook.com'),
        'instagram' => env('SHOP_SOCIAL_INSTAGRAM', 'https://instagram.com'),
        'x'         => env('SHOP_SOCIAL_X', 'https://x.com'),
        'youtube'   => env('SHOP_SOCIAL_YOUTUBE', 'https://youtube.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping & Tax
    |--------------------------------------------------------------------------
    |
    | Amounts are expressed in major units (e.g. 5.00) and the tax rate is a
    | percentage applied to the discounted subtotal.
    |
    */

    'shipping' => [
        'flat_rate'      => (float) env('SHOP_SHIPPING_FLAT_RATE', 5.00),
        'free_threshold' => (float) env('SHOP_FREE_SHIPPING_THRESHOLD', 50.00),
    ],

    'tax_rate' => (float) env('SHOP_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media_disk' => env('SHOP_MEDIA_DISK', 'public'),

    'images' => [
        'max_upload_kb'  => 4096,
        'max_dimension'  => 1600,
        'thumbnail_size' => 480,
        'quality'        => 82,
        'max_per_upload' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'per_page'                 => 12,
        'related_limit'            => 8,
        'recently_viewed_limit'    => 8,
        'search_suggestions_limit' => 6,
        'homepage_section_limit'   => 8,
        'navigation_cache_ttl'     => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart & Orders
    |--------------------------------------------------------------------------
    */

    'cart' => [
        'max_quantity_per_item'    => 10,
        'guest_cart_lifetime_days' => 30,
    ],

    'orders' => [
        'unpaid_ttl_minutes' => (int) env('SHOP_UNPAID_ORDER_TTL_MINUTES', 60),
    ],

    'reviews' => [
        'auto_approve' => env('SHOP_REVIEWS_AUTO_APPROVE', true),
    ],

];
