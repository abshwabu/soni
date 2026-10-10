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

## Store & Telegram Configuration

Add your store details and Telegram bot credentials to `.env`:

```env
TELEGRAM_BOT_TOKEN="your_telegram_bot_token"
TELEGRAM_ADMIN_CHAT_ID="your_telegram_admin_chat_id"
STORE_PHONE="+251911000000"
STORE_WHATSAPP="+251911000000"
STORE_ADDRESS="Bole Medhanialem, Addis Ababa, Ethiopia"
STORE_CURRENCY=ETB
```
