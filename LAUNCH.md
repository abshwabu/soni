# Soni Perfume — Production Launch Checklist

This document details the step-by-step checklist required before and immediately after going live with the **Soni Perfume** storefront.

---

## 1. Environment & Infrastructure Hardening

- [ ] **Environment Configuration (`.env`)**:
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_KEY` is generated (`php artisan key:generate`)
  - [ ] `APP_URL=https://soniperfume.com`
  - [ ] `DB_CONNECTION=sqlite` with absolute path to persistent database (`/var/www/soni-perfume/shared/database/database.sqlite`)
  - [ ] `QUEUE_CONNECTION=database` using isolated `queue_db` SQLite connection
  - [ ] `SESSION_SECURE_COOKIE=true` and `SESSION_HTTP_ONLY=true`
- [ ] **File System Permissions**:
  - [ ] App directory owned by `www-data:www-data` (`chown -R www-data:www-data /var/www/soni-perfume`)
  - [ ] SQLite database files permissions set to `chmod 640` on `*.sqlite*`
  - [ ] SQLite directory permissions set to `chmod 750`
  - [ ] Storage and cache writable: `chmod -R 775 storage bootstrap/cache`
- [ ] **Nginx Hardening**:
  - [ ] Direct access to `.sqlite`, `.sqlite3`, `.db`, `.wal`, `.shm`, `.bak`, `.gz`, and `.env` files returns HTTP `404` or `403`
  - [ ] Hidden files (`.git`, etc.) denied
  - [ ] Security headers enabled (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, CSP, HSTS)

---

## 2. Store Configuration in Filament Admin

- [ ] **Admin Authentication**:
  - [ ] Filament super-admin user created: `php artisan make:filament-user`
  - [ ] Default / seed admin password replaced with high-entropy password
  - [ ] Verified login at `https://soniperfume.com/admin`
- [ ] **Store Settings**:
  - [ ] Store name: `Soni Perfume`
  - [ ] Boutique physical address: `Bole Medhanialem, Addis Ababa, Ethiopia`
  - [ ] Customer support phone: `+251 91 100 0000` (and backup phone)
  - [ ] Telegram handle: `@soniperfume`
  - [ ] Delivery fee in Addis: `ETB 150` (or appropriate store rate)
  - [ ] Free delivery threshold: `ETB 3,000`
  - [ ] Announcement bar message updated with active promotion

---

## 3. Telegram Bot & Notification Pipeline

- [ ] **Bot Credentials**:
  - [ ] `TELEGRAM_BOT_TOKEN` set in `.env`
  - [ ] `TELEGRAM_ADMIN_CHAT_ID` set to store manager's Telegram user ID or group ID
  - [ ] `TELEGRAM_WEBHOOK_SECRET` generated (e.g. 32-character random string)
  - [ ] If running on a server without background queue workers, set `TELEGRAM_SYNC_DISPATCH=true` in `.env`
- [ ] **Artisan Diagnostics**:
  - [ ] Run `php artisan telegram:test --diagnose` to confirm bot authentication, queue health, and webhook status
  - [ ] Run `php artisan telegram:test --order` to verify full formatted order notification with interactive buttons
  - [ ] Run `php artisan telegram:set-webhook` to bind webhook to `https://soniperfume.com/telegram/webhook/{secret}`
  - [ ] Confirm Telegram webhook response: `"Webhook was set"`
- [ ] **Queue Worker (if using background queues)**:
  - [ ] Supervisor configured and active (`supervisorctl status soni-queue-worker:*`)
  - [ ] Supervisor executes `php artisan queue:work queue_db --sleep=3 --tries=3`
  - [ ] Queue failure alert: verified that job failures dispatch an alert to Telegram

---

## 4. End-to-End Test Order Verification

- [ ] **Cart Flow**:
  - [ ] Add fragrance to cart with variant selection (e.g., 50ml, 100ml)
  - [ ] Quantity update and line total recalculation verified in whole ETB
  - [ ] Free-delivery progress bar behaves accurately against `ETB 3,000` threshold
- [ ] **Guest Checkout**:
  - [ ] Fill customer name, Addis Ababa subcity/address, and Ethiopian phone (`0911223344` or `0711223344`)
  - [ ] Submit order and verify redirection to `/order/{order_number}/thank-you`
  - [ ] Confirm inventory stock decremented atomically
  - [ ] Confirm Telegram alert arrives instantly in admin group with customer details and inline action buttons
  - [ ] Click "Confirm" inline button on Telegram to verify status transition to `confirmed`
  - [ ] Verify test order status reflected in Filament Admin (`/admin/orders`)
  - [ ] Printable customer invoice rendered cleanly at `/admin/orders/{id}/invoice`

---

## 5. Backup, Maintenance & Disaster Recovery

- [ ] **Manual Backup Run**:
  - [ ] Run `php artisan db:backup`
  - [ ] Confirm generated `.sqlite.gz` for both `sqlite` and `queue_db` in `storage/app/backups`
  - [ ] Confirm media backup archive `media_*.tar.gz` created
- [ ] **Restoration Drill**:
  - [ ] Run `php artisan db:restore --help`
  - [ ] Verify restore integrity validation (`PRAGMA integrity_check`) works without issues
- [ ] **Scheduled Maintenance**:
  - [ ] Cron entry active for `www-data`: `* * * * * cd /var/www/soni-perfume/current && php artisan schedule:run >> /dev/null 2>&1`
  - [ ] Daily backup scheduled at `02:30`
  - [ ] Weekly SQLite maintenance (`db:maintenance-sqlite`) scheduled Sundays at `03:30`
  - [ ] Daily sitemap generation scheduled at `03:00`

---

## 6. Mobile Experience & UI Polish

- [ ] **Mobile Responsiveness**:
  - [ ] Tested on iOS Safari and Android Chrome
  - [ ] Slide-out mobile navigation drawer functions smoothly
  - [ ] Slide-out mini cart operates without layout shifting
  - [ ] Scent Finder quiz interactive on small screens
  - [ ] Phone call and WhatsApp direct contact buttons open appropriate native dialers
- [ ] **Accessibility & Performance**:
  - [ ] Respects `prefers-reduced-motion` for subtle animations
  - [ ] Fonts (Cormorant Garamond & Inter) loaded efficiently
  - [ ] High-contrast luxury color scheme (#FAF7F2 background, #1C1917 charcoal text, #B08D57 gold accents)

---

## 7. SEO, Analytics & Social Sharing

- [ ] **Sitemap & Robots**:
  - [ ] `https://soniperfume.com/sitemap.xml` accessible and returns valid XML
  - [ ] `https://soniperfume.com/robots.txt` disallows `/admin` and points to sitemap
- [ ] **Metadata & Open Graph**:
  - [ ] Dynamic `<title>` and `<meta name="description">` populated on all catalog pages
  - [ ] `og:image`, `og:title`, and `twitter:card` rendered accurately on product pages
  - [ ] JSON-LD structured data present (`Organization`, `LocalBusiness`, `Product`, `BreadcrumbList`)
- [ ] **301 Redirects**:
  - [ ] Verify product slug modifications retain historical 301 redirects to avoid broken backlinks

---

## 8. Go-Live Final Step

- [ ] DNS A records pointed to production VPS IPv4 address
- [ ] DNS AAAA records configured if IPv6 is available
- [ ] SSL certificate generated via Certbot (`certbot --nginx -d soniperfume.com -d www.soniperfume.com`)
- [ ] Run `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Verify storefront is live and serving HTTP 200 over HTTPS!
