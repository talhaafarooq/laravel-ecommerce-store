# ShopNest — Laravel e-commerce store

A full-featured online store built with **Laravel 13**, **React 19 + Inertia 3**, **TypeScript**, **Tailwind 4** and **shadcn/ui**, backed by **MySQL**.
It covers the storefront, customer accounts, checkout with **Cash on Delivery** and **Stripe**, an admin back office, and a **JWT REST API**.

> Originally specified for Django + Bootstrap; this implementation translates the same requirements to Laravel + React (Inertia), with Nginx + PHP-FPM replacing Gunicorn.

## Features

| Area | Highlights |
|------|-----------|
| **Storefront** | Home, shop with category / brand / price / rating / stock / sale filters and sorting, live search suggestions, product pages with galleries, related and recently viewed products, wishlist, reviews & ratings, blog, FAQ, contact, about, sitemap, SEO titles/meta/Open Graph |
| **Cart & checkout** | Guest carts merged on login, stock-aware quantities, coupons (percentage / fixed, limits, expiry), saved addresses, free-shipping progress, order notes |
| **Payments** | Cash on Delivery and Stripe Checkout, signature-verified webhooks, amount/currency verification, idempotent processing, retry-payment, automatic cancellation of unpaid orders |
| **Accounts** | Register, login, logout, email verification, forgot/reset password, profile with avatar, change password, 2FA, passkeys, address book, order history, PDF invoices |
| **Admin** | KPI dashboard, sales reports with CSV export, products with multi-image upload, categories, brands, orders with lifecycle transitions, customers & roles, inventory ledger, coupons, review moderation, blog, FAQs, contact inbox |
| **API** | Versioned REST API with JWT auth (`/api/v1`), auto-generated OpenAPI docs at `/docs/api` |
| **Quality** | 195 Pest tests, PHPStan level 7, Pint (strict ruleset), oxlint/oxfmt, TypeScript strict, CI on GitHub Actions |
| **Security** | CSRF, XSS-safe Markdown, parameterised queries, policies/gates, rate limiting, security headers, upload re-encoding, hardened cache, verified webhooks |

## Tech stack

Laravel 13 · PHP 8.4 · MySQL 8 · Inertia v3 · React 19 · TypeScript · Tailwind CSS 4 · shadcn/ui (Radix) · Recharts · Vite (vite-plus) · Fortify · php-open-source-saver/jwt-auth · Stripe PHP SDK · barryvdh/laravel-dompdf · Intervention Image · Scramble (OpenAPI) · Pest 5 · Larastan · Laravel Pint.

## Quick start

Requirements: PHP ≥ 8.3 with `gd`, `intl`, `pdo_mysql`; Composer; Node ≥ 22; MySQL 8 (the project is configured for MAMP on port **8889**, `root`/`root`).

```bash
git clone https://github.com/talhaafarooq/laravel-ecommerce-store.git shopnest
cd shopnest

composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret            # fills JWT_SECRET

# create the databases (MAMP shown; adjust to your setup)
mysql -h127.0.0.1 -P8889 -uroot -proot -e "CREATE DATABASE ecommerce; CREATE DATABASE ecommerce_testing;"

php artisan migrate --seed        # ~4 min the first time: downloads and optimises 380 demo photos (cached afterwards)
php artisan storage:link

npm install
npm run dev                       # or: npm run build
php artisan serve                 # http://localhost:8000
```

`composer dev` runs the server, queue worker and Vite together.

### Demo accounts (password: `password`)

| Role | Email |
|------|-------|
| Administrator | `admin@shopnest.test` |
| Customer | `customer@shopnest.test` |

The seeder creates 182 products in 7 departments, ~540 reviews, coupons (`WELCOME10`, `SAVE20`, `FLASH25`), a 90-day order history, blog posts, FAQs and contact messages. Product photos come from the DummyJSON demo CDN; offline, neutral placeholders are generated.

### Configuration

All store settings live in `.env` and `config/shop.php` — currency, shipping rate and free-shipping threshold, tax rate, image limits, catalogue page sizes, unpaid-order window. Stripe is optional: leave the keys empty and only Cash on Delivery is offered.

```dotenv
STRIPE_KEY=pk_test_…
STRIPE_SECRET=sk_test_…
STRIPE_WEBHOOK_SECRET=whsec_…     # stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

## Architecture

```
Request → Form Request → Controller (thin) → Service / use case → Repository interface → Eloquent
```

```
app/
├── Contracts/Repositories   interfaces (bound in RepositoryServiceProvider)
├── Repositories             query logic, row locks
├── Services                 business workflows (Cart, Checkout, Orders, Inventory, Payments, …)
├── DTOs / ValueObjects      Money (integer minor units), DateRange, filters, checkout data
├── Enums                    OrderStatus (state machine), PaymentMethod, StockStatus, …
├── Http                     Controllers (Storefront, Account, Admin, Api/V1), Requests, Resources, Middleware
├── Policies                 Order, Address, Review
├── Events / Listeners / Notifications   queued, after-commit
└── Exceptions               BusinessRuleException hierarchy (JSON for APIs, toast + redirect for pages)
resources/js
├── pages                    Inertia pages (storefront, account, admin)
├── components               shop/, admin/, charts/, ui/ (shadcn)
└── layouts                  store, account, admin (page-level), auth
```

Key decisions: money is stored as integers; checkout, stock and payment changes run in transactions with `SELECT … FOR UPDATE` so concurrent orders cannot oversell; order lines snapshot name, SKU and price; the admin bundle is split from the storefront bundle; chart colours were validated for contrast and colour-blind separation.

## Documentation

- [REST API](docs/API.md) — also browsable at `/docs/api` (OpenAPI at `/docs/api.json`)
- [Database schema](docs/DATABASE.md)
- [Deployment (Nginx + PHP-FPM)](docs/DEPLOYMENT.md)

## Development

```bash
composer test         # Pint check + PHPStan (level 7) + Pest
composer lint         # fix code style
npm run check         # oxlint + oxfmt (npm run check:fix to fix)
npm run types:check   # TypeScript
php artisan test --compact
```

Tests run against the `ecommerce_testing` MySQL database.

## Scheduled tasks

`php artisan schedule:run` every minute (cron) runs `orders:cancel-unpaid` (releases stock held by unpaid online orders after `SHOP_UNPAID_ORDER_TTL_MINUTES`) and `model:prune` (abandoned guest carts).

## License

MIT
