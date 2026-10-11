# Soni Perfume Store

Modern e-commerce-style storefront for a local perfume store in Addis Ababa, Ethiopia.

## Stack & Architecture

- **Backend:** Laravel 11 (PHP 8.2+)
- **Storefront:** Inertia.js + Vue 3 (script setup), Tailwind CSS, Vite
- **Admin Panel:** Filament v3 (accessible at `/admin`)
- **Database:** Dual-SQLite architecture with WAL mode:
  - `database/database.sqlite` (`sqlite` connection): Products, categories, orders, customers, media
  - `database/queue.sqlite` (`queue_db` connection): Cache, sessions, and queue jobs (prevents write contention with order transactions)
- **Queues:** Laravel queues using `database` driver on `queue_db` connection
- **Media & Images:** `spatie/laravel-medialibrary` with WebP conversions
- **SEO & Slugs:** `spatie/laravel-sluggable`, `spatie/laravel-sitemap`
- **Typography:** Self-hosted Fontsource fonts:
  - **Headings:** *Cormorant Garamond*
  - **Body:** *Inter*
- **Localization:** `vue-i18n` with English (`en.json`) structured for easy Amharic (`am.json`) addition

---

## Getting Started

### 1. Requirements

- PHP 8.2+ with `pdo_sqlite`, `exif`, `fileinfo`, `intl`, `mbstring` extensions
- SQLite 3.35+
- Node.js 18+ and npm
- Composer 2+

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure absolute SQLite paths in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/project/database/database.sqlite
QUEUE_DB_DATABASE=/path/to/project/database/queue.sqlite
DB_CACHE_CONNECTION=queue_db
DB_CACHE_LOCK_CONNECTION=queue_db
DB_QUEUE_CONNECTION=queue_db
SESSION_CONNECTION=queue_db

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

> **Production Path Note:** On production servers (e.g. Ubuntu / Nginx), ensure `DB_DATABASE` and `QUEUE_DB_DATABASE` point to the absolute production path:
> ```env
> DB_DATABASE=/var/www/soni-perfume/database/database.sqlite
> QUEUE_DB_DATABASE=/var/www/soni-perfume/database/queue.sqlite
> ```
> Ensure directory permissions allow the web server user (e.g. `www-data`) read and write access to the `database/` directory and sqlite files for WAL journal creation (`.sqlite-wal`, `.sqlite-shm`).

### 3. Migrations and Database Seeding

Run database migrations and seed the initial admin account:

```bash
php artisan migrate --seed
```

Both SQLite database files (`database.sqlite` and `queue.sqlite`) are created automatically with `PRAGMA journal_mode = wal;`.

You can verify WAL mode via:

```bash
sqlite3 database/database.sqlite "PRAGMA journal_mode;"
# Output: wal

sqlite3 database/queue.sqlite "PRAGMA journal_mode;"
# Output: wal
```

### 4. Admin Access

The Filament admin panel is located at `/admin`.

Default seeded credentials (configured via `.env`):
- **Email:** `admin@soniperfume.com` (or value of `ADMIN_EMAIL`)
- **Password:** `password` (or value of `ADMIN_PASSWORD`)

Customer registration and login routes have been removed from the public storefront; authentication is reserved strictly for store administration via Filament.

### 5. Frontend Development

Install dependencies and start the Vite dev server:

```bash
npm install
npm run dev
```

In another terminal, start the Laravel server:

```bash
php artisan serve
```

Visit `http://localhost:8000` to view the storefront.

---

## Production Queue & Supervisor Configuration

Because the queue is processed asynchronously and isolated in `database/queue.sqlite`, worker jobs never lock order transactions in `database.sqlite`.

### Supervisor Configuration

Create `/etc/supervisor/conf.d/soni-perfume-worker.conf`:

```ini
[program:soni-perfume-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/soni-perfume/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/soni-perfume/storage/logs/worker.log
stopwaitsecs=3600
```

Start the Supervisor worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start soni-perfume-worker:*
```

To run manually during development:

```bash
php artisan queue:work database
```

---

## Store & Telegram Bot Configuration

Soni Perfume integrates with Telegram Bot API to notify administrators of new customer orders and stock warnings instantly, and allows order status management directly from Telegram chats via interactive inline buttons.

### 1. Create Bot with @BotFather

1. Open Telegram and search for [@BotFather](https://t.me/BotFather).
2. Send `/newbot` and follow the prompts:
   - Name: `Soni Perfume Admin`
   - Username: `SoniPerfumeBot` (must end in `bot`)
3. Copy the generated HTTP API Bot Token (e.g. `123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ`).

### 2. Obtain your Admin Chat ID

1. Search for your newly created bot on Telegram and click **Start** (or send `/start`).
2. The bot responds with your numeric Chat ID:
   ```text
   👋 Welcome to Soni Perfume Store Bot!
   Your Chat ID is: 987654321
   ```
3. If using an admin Telegram group, add your bot to the group with admin privileges, send `/start`, and use the group chat ID (typically starting with `-100`).

### 3. Configure Environment Variables

Add your bot token and admin chat ID(s) in `.env`:

```env
TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
TELEGRAM_ADMIN_CHAT_ID="987654321"
TELEGRAM_WEBHOOK_SECRET="your-random-secret-key"

STORE_PHONE="+251911000000"
STORE_WHATSAPP="+251911000000"
STORE_ADDRESS="Bole Medhanialem, Addis Ababa, Ethiopia"
STORE_CURRENCY=ETB
```

> **Multiple Admins:** You can specify multiple authorized admin IDs separated by commas:
> `TELEGRAM_ADMIN_CHAT_ID="987654321,123456789,-100987654321"`

### 4. Register Webhook (HTTPS Required)

Telegram Bot API requires an **HTTPS** URL for webhook callbacks:

- **Local Development:** Use a tunnel service like Cloudflare Tunnel or ngrok:
  ```bash
  ngrok http 8000
  php artisan telegram:set-webhook --url=https://your-tunnel.ngrok-free.app
  ```
- **Production Server:** Ensure `APP_URL` in `.env` starts with `https://`:
  ```bash
  php artisan telegram:set-webhook
  ```

### 5. Test Bot Connection

Verify your bot credentials and chat ID connectivity:

```bash
php artisan telegram:test
```

### 6. Telegram Bot Features & Commands

- **Order Notifications:** Every checkout automatically formats a notification with customer details, delivery location, notes, items snapshot, ETB totals, and timestamp in the `Africa/Addis_Ababa` timezone.
- **Inline Action Buttons:**
  - `[✅ Confirm]`: Changes order status to Confirmed.
  - `[🚚 Out for delivery]`: Changes order status to Out for Delivery.
  - `[✔️ Completed]`: Changes order status to Completed.
  - `[❌ Cancel]`: Cancels the order and atomically restores product variant stock in SQLite (guaranteed once only).
  - `[📞 Call customer]`: Direct phone call URL (`tel:+251...`).
  - `[💬 WhatsApp]`: Pre-populated WhatsApp chat link with the customer.
  - `[🔎 Open in Admin]`: Deep link to the order in the Filament admin panel.
- **Bot Commands for Admins:**
  - `/start`: Displays the sender's Chat ID and setup instructions.
  - `/today`: Displays today's order count, total ETB revenue, and status breakdown.
  - `/pending`: Lists all unfulfilled/new orders with direct links to the admin panel.
- **Low-Stock Alerts:**
  - When product variant stock drops below 3 units after an order or manual adjustment, a warning notification is sent to the admin chat (rate-limited/debounced to prevent spam).
- **Filament Integration:**
  - Orders in `/admin` display Telegram forwarding status and message ID.
  - The **"Resend to Telegram"** action dispatches the `SendOrderToTelegram` queued job with retry support.

---

## Content Pages, SEO & Performance

### 1. Store Pages & CMS
- **`/about`:** The boutique story, core values, authenticity promise, and gallery of store photos.
- **`/contact`:** Boutique address in Bole Medhanialem, opening hours, phone, WhatsApp, Telegram, embedded map, and direct inquiry form forwarded immediately to the admin Telegram chat.
- **`/gifts`:** Curated fragrance gift guide (For Him, For Her, Under ETB 3,000, Luxury & Statement, and Boxed Gift Sets) with complimentary gift box packaging notes.
- **`/delivery-returns`, `/privacy`, `/terms`:** CMS policy pages managed directly from the Filament admin panel under `Content -> Pages` with rich text editing.

### 2. Search Engine Optimization (SEO)
- **Dynamic Meta & Social Cards:** Comprehensive `<SeoHead>` component on every page providing dynamic title, description, canonical link, OpenGraph, and Twitter Summary Card metadata.
- **Structured Data (JSON-LD):** Auto-injected Schema.org markup for `Organization`, `LocalBusiness` (Bole Medhanialem address, geo coordinates, opening hours), `Product` (with price in ETB, availability, brand), and `BreadcrumbList`.
- **Sitemap & Robots:**
  - Live XML endpoint at `/sitemap.xml` with caching.
  - Generate static file via artisan command: `php artisan sitemap:generate`.
  - Configured `public/robots.txt` disallowing checkout, cart, and admin routes.
- **301 Clean Slug Redirects:** Automatic recording in `slug_redirects` table when a product's slug is updated, redirecting previous URLs via HTTP 301 permanent redirect.

### 3. Performance & SQLite Optimization
- **N+1 Prevention:** `Model::preventLazyLoading(! app()->isProduction())` enabled to enforce eager loading.
- **Slow Query Logging:** Automatic warning logs for queries exceeding 50ms in non-production.
- **Query & Bundle Caching:** Navigation categories, store settings, and home page collections cached with short TTL and automatically invalidated upon model save/delete events.
- **SQLite Maintenance:** Scheduled daily `PRAGMA optimize;` (`php artisan db:optimize-sqlite`) and weekly `PRAGMA integrity_check;` + `PRAGMA wal_checkpoint(TRUNCATE);` (`php artisan db:maintenance-sqlite`).
- **Asset Optimization:** WebP image conversions, responsive `srcset`, font preloading, and code-split chunks.
- **Analytics:** Optional Plausible Analytics or GA4 integration via `PLAUSIBLE_DOMAIN` and `GA4_MEASUREMENT_ID` tracking `add_to_cart`, `begin_checkout`, `order_placed`, and `welcome_modal_signup`.

---

## Production Deployment & Operations Guide

### 1. Single-Server VPS Architecture (Ubuntu 24.04/22.04 LTS)

Soni Perfume is designed for high efficiency on a single VPS (e.g. 2 vCPU, 2-4GB RAM) running:
- **Web Server:** Nginx with HTTP/2 and gzip compression
- **Runtime:** PHP-FPM 8.3 (`php8.3-fpm`, `php8.3-sqlite3` with FTS5 enabled, `php8.3-intl`, `php8.3-mbstring`, `php8.3-xml`, `php8.3-curl`, `php8.3-zip`)
- **Database:** SQLite 3 in WAL mode (Dual databases: main store + queue/cache)
- **SSL:** Free automated TLS via Let's Encrypt Certbot
- **Process Manager:** Supervisor for queue workers

### 2. VPS System Setup

```bash
# Update and install system dependencies
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx curl git unzip sqlite3 libsqlite3-dev supervisor certbot python3-certbot-nginx

# Install PHP 8.3 repository & extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-curl php8.3-mbstring \
    php8.3-xml php8.3-zip php8.3-intl php8.3-bcmath php8.3-gd

# Verify SQLite FTS5 extension is available
php -r "new PDO('sqlite::memory:')->query('CREATE VIRTUAL TABLE t USING fts5(x);'); echo 'FTS5 OK' . PHP_EOL;"
```

### 3. Directory Layout & Shared Symlinks

The production server uses a zero-downtime release directory structure:

```text
/var/www/soni-perfume/
├── current -> /var/www/soni-perfume/releases/20261011120000
├── releases/
│   ├── 20261011110000/
│   └── 20261011120000/
└── shared/
    ├── .env
    ├── backups/
    ├── database/
    │   ├── database.sqlite
    │   ├── database.sqlite-wal
    │   ├── database.sqlite-shm
    │   ├── queue.sqlite
    │   ├── queue.sqlite-wal
    │   └── queue.sqlite-shm
    └── storage/
        ├── app/public/
        ├── framework/
        └── logs/
```

### 4. SQLite Permissions & Web Root Isolation

To protect store databases from web exposure and corruption:
1. Databases are kept in `/var/www/soni-perfume/shared/database/`, strictly outside the Nginx document root (`/var/www/soni-perfume/current/public`).
2. Nginx explicitly denies all requests targeting `.sqlite`, `.db`, `.wal`, `.shm`, `.bak`, and `.env` files with HTTP 404.
3. Database permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/soni-perfume/shared
   sudo chmod 750 /var/www/soni-perfume/shared/database
   sudo chmod 640 /var/www/soni-perfume/shared/database/*.sqlite*
   ```

### 5. Automated Zero-Downtime Deployment

Execute the deployment script located in `deploy/deploy.sh`:

```bash
# From VPS server
chmod +x /var/www/soni-perfume/current/deploy/deploy.sh
./deploy/deploy.sh
```

The script:
1. Clones the latest release from GitHub into a timestamped directory.
2. Symlinks the persistent `.env`, `database/`, and `storage/` directories.
3. Installs optimized Composer dependencies without dev packages.
4. Builds frontend assets with Vite.
5. Runs `php artisan migrate --force`.
6. Primes configuration, routes, views, icons, and Filament caches.
7. Atomically switches the `current` symlink.
8. Restarts the queue worker and reloads PHP-FPM with zero downtime.
9. Retains the last 5 releases and prunes older ones.

### 6. Nginx & Supervisor Setup

- Copy `deploy/nginx.conf` to `/etc/nginx/sites-available/soni-perfume` and symlink to `/etc/nginx/sites-enabled/`.
- Copy `deploy/supervisor.conf` to `/etc/supervisor/conf.d/soni-queue-worker.conf`.
- Start the supervisor queue workers:
  ```bash
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start soni-queue-worker:*
  ```

### 7. Scheduled Tasks & Cron

Add the Laravel scheduler to the `www-data` user crontab (`sudo crontab -u www-data -e`):

```cron
* * * * * cd /var/www/soni-perfume/current && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs include:
- `db:optimize-sqlite`: Daily at 02:00 (SQLite query planner stats update)
- `db:backup`: Daily at 02:30 (WAL-safe VACUUM INTO snapshot, gzip compression, 14-day retention, media tar.gz)
- `sitemap:generate`: Daily at 03:00 (Catalog sitemap refresh)
- `db:maintenance-sqlite`: Weekly on Sundays at 03:30 (`PRAGMA integrity_check` & `PRAGMA wal_checkpoint(TRUNCATE)`)

---

## Backup & Disaster Recovery

### 1. Running Backups

Perform an on-demand, WAL-safe atomic backup at any time:

```bash
php artisan db:backup
```

Options:
- `--days=14`: Retain backups newer than 14 days (default: 14).
- `--no-media`: Exclude public media attachments.
- `--destination=/path/to/backups`: Custom backup directory.

Backups are saved to `storage/app/backups/`:
- `sqlite_YYYY-MM-DD_HHMMSS.sqlite.gz`: Main database snapshot
- `queue_db_YYYY-MM-DD_HHMMSS.sqlite.gz`: Queue and cache database snapshot
- `media_YYYY-MM-DD_HHMMSS.tar.gz`: Media archive

### 2. Restoring a Database

To restore from a backup:

```bash
# Interactive selection from available backups:
php artisan db:restore

# Direct file restore with pre-validation:
php artisan db:restore storage/app/backups/sqlite_2026-10-11_034305.sqlite.gz --force
```

**Restoration Safety Features:**
1. Decompresses into a temporary file and runs `PRAGMA integrity_check;` before touching the active database.
2. Backs up the current database to `database.sqlite.pre_restore.bak`.
3. Purges active PDO connections to release locks.
4. Replaces the file and deletes stale `-wal` and `-shm` index files to prevent journal corruption.
5. Reconnects and reapplies WAL mode (`PRAGMA journal_mode = wal;`).

---

## Security Hardening & Rate Limits

1. **Security Headers Middleware:**
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: SAMEORIGIN`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (enforced over HTTPS)
   - Strict `Content-Security-Policy` permitting self-hosted assets, Google Fonts, Plausible, and Telegram API.
2. **Rate Limiting:**
   - Checkout: Max 5 orders per hour per IP + route throttle `15,1`
   - Contact inquiries: Max 5 messages per hour per IP + route throttle `10,1`
   - Search suggestions: Route throttle `60,1`
   - Newsletter subscriptions: Max 10 attempts per minute + route throttle `10,1`
3. **Bot Token Redaction:**
   - Any log output or exception in `TelegramService` automatically masks sensitive bot credentials (`bot[REDACTED_BOT_TOKEN]`).
4. **Rich Text Input Sanitization:**
   - `Page` model automatically strips `<script>`, `<iframe>`, inline JavaScript event handlers (`onclick`, `onerror`, etc.), and dangerous URI protocols (`javascript:`) before storing rich content.
5. **Filament Admin Authorization:**
   - Customer login and registration are removed; admin routes (`/admin`) require authenticated administrative credentials.



