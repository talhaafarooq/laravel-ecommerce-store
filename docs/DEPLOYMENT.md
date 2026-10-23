# Deployment — Nginx + PHP-FPM

The Django guide's *Nginx + Gunicorn* maps to **Nginx + PHP-FPM** here. Ready-made configs are in [`deploy/`](../deploy).

## 1. Server prerequisites (Ubuntu 24.04 example)

```bash
sudo apt install nginx mysql-server supervisor redis-server git unzip \
  php8.4-fpm php8.4-{cli,mysql,mbstring,xml,curl,gd,intl,bcmath,zip,redis}
# Node 22 + Composer from their official installers
```

Create the database and user:

```sql
CREATE DATABASE shopnest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shopnest'@'localhost' IDENTIFIED BY '<strong password>';
GRANT ALL ON shopnest.* TO 'shopnest'@'localhost';
```

## 2. Application

```bash
sudo mkdir -p /var/www/shopnest && sudo chown $USER: /var/www/shopnest
git clone https://github.com/talhaafarooq/laravel-ecommerce-store.git /var/www/shopnest
cd /var/www/shopnest
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate && php artisan jwt:secret
```

Edit `.env` for production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shop.example.com
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopnest
DB_USERNAME=shopnest
DB_PASSWORD=…
CACHE_STORE=redis        # or database
QUEUE_CONNECTION=redis   # or database
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp         # + MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM_ADDRESS
STRIPE_KEY=…  STRIPE_SECRET=…  STRIPE_WEBHOOK_SECRET=…
SHOP_ADMIN_EMAIL=orders@example.com
```

```bash
npm ci && npm run build:ssr
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

Create the first administrator (do **not** run the demo seeder in production):

```bash
php artisan shop:create-admin you@example.com --name="Store Owner"
```

## 3. Nginx

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/shopnest
sudo ln -s /etc/nginx/sites-available/shopnest /etc/nginx/sites-enabled/
sudo certbot --nginx -d shop.example.com
sudo nginx -t && sudo systemctl reload nginx
```

## 4. Background processes

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/shopnest.conf
sudo supervisorctl reread && sudo supervisorctl update
echo '* * * * * www-data cd /var/www/shopnest && php artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/shopnest
```

The queue worker sends emails and notifications; the scheduler cancels unpaid online orders and prunes abandoned carts. Set `INERTIA_SSR_ENABLED=false` if you do not want to run the SSR process.

## 5. Stripe

1. Add a webhook endpoint `https://shop.example.com/api/webhooks/stripe` with the four `checkout.session.*` events listed in [API.md](API.md).
2. Put its signing secret in `STRIPE_WEBHOOK_SECRET`, then `php artisan config:cache`.

## 6. Releasing updates

`deploy/deploy.sh` puts the site in maintenance mode, pulls, installs, builds, migrates, rebuilds caches, restarts workers and brings the site back.

## Checklist

- [ ] `APP_DEBUG=false`, strong `APP_KEY` and `JWT_SECRET`, `.env` not world-readable
- [ ] HTTPS everywhere (HSTS is sent automatically in production)
- [ ] `storage/` and `bootstrap/cache/` writable by the PHP-FPM user
- [ ] Queue worker and scheduler running
- [ ] Mail configured and the Stripe webhook verified (`stripe trigger checkout.session.completed`)
- [ ] Database and `storage/app/public` backed up
