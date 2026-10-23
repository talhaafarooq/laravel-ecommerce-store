# REST API

Base URL: `/api/v1` · Format: JSON · Auth: **JWT bearer tokens** · Interactive docs: `/docs/api` (OpenAPI: `/docs/api.json`).

```http
Authorization: Bearer <access_token>
Accept: application/json
```

Single resources are wrapped in `data`; lists are paginated with `data`, `links` and `meta`. Money is always an object:

```json
{ "amount": 1999, "decimal": "19.99", "formatted": "$19.99", "currency": "USD" }
```

## Authentication

| Method | Path             | Description                                             |
| ------ | ---------------- | ------------------------------------------------------- |
| POST   | `/auth/register` | Create a customer account → `201` with token + user     |
| POST   | `/auth/login`    | `email`, `password`, optional `two_factor_code` → token |
| GET    | `/auth/me`       | Current user                                            |
| POST   | `/auth/refresh`  | Exchange the token for a fresh one                      |
| POST   | `/auth/logout`   | Invalidate the token                                    |

Token response: `{ "access_token", "token_type": "bearer", "expires_in": 3600, "user": {…} }`. Login and registration are limited to 10 requests/minute per IP; everything else to 60/minute.

## Catalogue (public)

| Method | Path                                | Description                                                                                                                                                                                                              |
| ------ | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| GET    | `/products`                         | Filters: `q`, `category` (slug), `brands[]`, `min_price`, `max_price`, `min_rating`, `in_stock`, `on_sale`, `sort` (`newest`, `popular`, `rating`, `price_asc`, `price_desc`, `name_asc`), `per_page` (12/24/48), `page` |
| GET    | `/products/search?q=`               | Lean typeahead results (min. 2 characters)                                                                                                                                                                               |
| GET    | `/products/{slug}`                  | Product detail with images                                                                                                                                                                                               |
| GET    | `/products/{slug}/related`          | Related products                                                                                                                                                                                                         |
| GET    | `/products/{slug}/reviews`          | Approved reviews (paginated)                                                                                                                                                                                             |
| GET    | `/categories`, `/categories/{slug}` | Category tree / one category                                                                                                                                                                                             |

## Authenticated endpoints

| Method         | Path                                         | Description                                          |
| -------------- | -------------------------------------------- | ---------------------------------------------------- |
| POST           | `/profile`                                   | Update name, email, phone, avatar (multipart)        |
| GET / DELETE   | `/cart`                                      | View / clear the cart                                |
| POST           | `/cart/items`                                | `{ product_id, quantity }` → updated cart (`201`)    |
| PATCH / DELETE | `/cart/items/{item}`                         | Change quantity / remove a line                      |
| POST / DELETE  | `/cart/coupon`                               | Apply (`{ code }`) / remove a coupon                 |
| GET            | `/orders`                                    | Your orders (paginated)                              |
| POST           | `/orders`                                    | Place an order from the cart (see below)             |
| GET            | `/orders/{number}`                           | Order detail (owner only)                            |
| POST           | `/orders/{number}/cancel`                    | Cancel a pending, unpaid order                       |
| GET            | `/wishlist` · POST/DELETE `/wishlist/{slug}` | Wishlist                                             |
| POST           | `/products/{slug}/reviews`                   | Create or replace your review (`rating` 1–5, `body`) |
| DELETE         | `/reviews/{id}`                              | Delete your review                                   |

### Placing an order

```http
POST /api/v1/orders
{
  "shipping": { "full_name": "Jordan Rivera", "phone": "+1 555 010 0002", "line1": "742 Evergreen Terrace",
                "city": "Springfield", "state": "OR", "postal_code": "97403", "country": "US" },
  "payment_method": "cod",        // or "stripe"
  "notes": "Leave at the door"
}
```

`201` returns the order plus `meta.payment_url` — for `stripe`, redirect the customer there to pay. If Stripe is unreachable the order is still created and `meta.payment_error` explains why; retry from the web UI.

## Webhook

`POST /api/webhooks/stripe` — verified with the `Stripe-Signature` header and `STRIPE_WEBHOOK_SECRET`. Subscribe to `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` and `checkout.session.expired`.

## Errors

| Status | Meaning                                                   |
| ------ | --------------------------------------------------------- |
| 401    | Missing, invalid or expired token                         |
| 402    | Payment method unavailable / payment could not be started |
| 403    | Authenticated but not allowed (e.g. someone else's order) |
| 404    | Not found (also used for inactive products)               |
| 409    | Not enough stock                                          |
| 422    | Validation or business rule failure                       |
| 429    | Rate limited                                              |

```json
{
    "message": "Only 2 left of \"Desk Lamp\".",
    "errors": { "quantity": ["Only 2 left of \"Desk Lamp\"."] }
}
```
