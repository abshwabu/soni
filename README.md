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
- **SQLite Maintenance:** Daily scheduled `PRAGMA optimize;` command (`php artisan db:optimize-sqlite`) keeping SQLite query planner statistics primed.
- **Asset Optimization:** WebP image conversions, responsive `srcset`, font preloading, and code-split chunks.
- **Analytics:** Optional Plausible Analytics or GA4 integration via `PLAUSIBLE_DOMAIN` and `GA4_MEASUREMENT_ID` tracking `add_to_cart`, `begin_checkout`, `order_placed`, and `welcome_modal_signup`.


