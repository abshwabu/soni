#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Soni Perfume — Zero-Downtime VPS Deployment Script
# Single-server Ubuntu VPS + Nginx + PHP 8.3 FTS5 + SQLite WAL
# ==============================================================================

# Configuration
APP_NAME="soni-perfume"
BASE_DIR="/var/www/${APP_NAME}"
SHARED_DIR="${BASE_DIR}/shared"
RELEASES_DIR="${BASE_DIR}/releases"
CURRENT_LINK="${BASE_DIR}/current"
TIMESTAMP=$(date +"%Y%m%d%H%M%S")
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"
KEEP_RELEASES=5
REPO_URL="https://github.com/Abshewabu/soni.git"
BRANCH="main"

echo "========================================================"
echo " Starting Zero-Downtime Deployment: ${APP_NAME} (${TIMESTAMP})"
echo "========================================================"

# 1. Ensure directory structure exists
mkdir -p "${SHARED_DIR}/database"
mkdir -p "${SHARED_DIR}/storage/app/public"
mkdir -p "${SHARED_DIR}/storage/framework/cache"
mkdir -p "${SHARED_DIR}/storage/framework/sessions"
mkdir -p "${SHARED_DIR}/storage/framework/views"
mkdir -p "${SHARED_DIR}/storage/logs"
mkdir -p "${SHARED_DIR}/backups"
mkdir -p "${RELEASES_DIR}"

# 2. Clone release from repository
echo "📥 Cloning release from ${BRANCH} branch..."
git clone --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${RELEASE_DIR}"
cd "${RELEASE_DIR}"

# 3. Setup shared environment and databases
echo "🔗 Linking shared persistent resources..."

# Environment file
if [ ! -f "${SHARED_DIR}/.env" ]; then
    echo "⚠️  ${SHARED_DIR}/.env not found! Creating from .env.example..."
    cp "${RELEASE_DIR}/.env.example" "${SHARED_DIR}/.env"
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${SHARED_DIR}/database/database.sqlite|g" "${SHARED_DIR}/.env"
    sed -i "s|APP_ENV=.*|APP_ENV=production|g" "${SHARED_DIR}/.env"
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|g" "${SHARED_DIR}/.env"
fi
ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"

# Primary and Queue SQLite databases
if [ ! -f "${SHARED_DIR}/database/database.sqlite" ]; then
    touch "${SHARED_DIR}/database/database.sqlite"
fi
if [ ! -f "${SHARED_DIR}/database/queue.sqlite" ]; then
    touch "${SHARED_DIR}/database/queue.sqlite"
fi

# Ensure correct permissions on SQLite database directory and files
chmod 750 "${SHARED_DIR}/database"
chmod 640 "${SHARED_DIR}/database/"*.sqlite* 2>/dev/null || true

# Symlink databases to release database directory
rm -f "${RELEASE_DIR}/database/database.sqlite"
rm -f "${RELEASE_DIR}/database/queue.sqlite"
ln -sfn "${SHARED_DIR}/database/database.sqlite" "${RELEASE_DIR}/database/database.sqlite"
ln -sfn "${SHARED_DIR}/database/queue.sqlite" "${RELEASE_DIR}/database/queue.sqlite"

# Storage directory symlink
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

# 4. Install PHP dependencies
echo "📦 Installing PHP composer dependencies (optimized, no-dev)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 5. Build Vite frontend assets
echo "🎨 Building frontend production bundle with Vite..."
if command -v npm &> /dev/null; then
    npm ci --silent
    npm run build
fi

# 6. Database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# 7. Create storage symlink
php artisan storage:link || true

# 8. Caching and framework optimization
echo "⚡ Optimizing application configuration and caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache || true
php artisan filament:optimize || true

# 9. Set permissions for web server (www-data)
echo "🔒 Enforcing secure permissions..."
chown -R www-data:www-data "${RELEASE_DIR}" "${SHARED_DIR}"
chmod -R 775 "${SHARED_DIR}/storage" "${RELEASE_DIR}/bootstrap/cache"

# 10. Atomic symlink swap (Zero Downtime)
echo "🔄 Atomically switching active symlink..."
ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}.tmp"
mv -Tf "${CURRENT_LINK}.tmp" "${CURRENT_LINK}"

# 11. Restart background workers and reload PHP-FPM
echo "🔄 Restarting queue workers and reloading PHP-FPM..."
php "${CURRENT_LINK}/artisan" queue:restart

if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart soni-queue-worker:* || true
fi

if systemctl is-active --quiet php8.3-fpm; then
    sudo systemctl reload php8.3-fpm
fi

# 12. Prune old releases
echo "🧹 Cleaning up old releases (retaining last ${KEEP_RELEASES})..."
cd "${RELEASES_DIR}"
ls -1dt */ 2>/dev/null | tail -n +$((KEEP_RELEASES + 1)) | xargs -I {} rm -rf "{}" || true

echo "========================================================"
echo "✅ Deployment completed successfully at $(date)!"
echo "   Active release: ${RELEASE_DIR}"
echo "========================================================"
