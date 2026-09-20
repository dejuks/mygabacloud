#!/usr/bin/env bash
#
# Marketplace installer.
# Run this from INSIDE a fresh Laravel project directory, after copying
# the contents of this package into it.
#
set -e

echo "==> Checking for Laravel..."
if [ ! -f artisan ]; then
  echo "ERROR: no artisan file found."
  echo "Run this from inside your Laravel project directory."
  exit 1
fi

echo "==> Checking config/filesystems.php has the 'private' disk..."
if ! grep -q "'private'" config/filesystems.php 2>/dev/null; then
  echo "ERROR: config/filesystems.php does not define a 'private' disk."
  echo "Copy config/filesystems.php from this package over your project's copy first."
  exit 1
fi

echo "==> Installing Breeze (auth scaffolding)..."
composer require laravel/breeze --dev --no-interaction
php artisan breeze:install blade --no-interaction

echo "==> Installing frontend dependencies..."
npm install
npm run build

echo "==> Creating storage symlink..."
php artisan storage:link

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding demo data..."
php artisan db:seed --class=DemoSeeder --force

echo "==> Marking as installed (skips the web setup wizard)..."
mkdir -p storage
cat > storage/installed.lock <<EOF
{"installed_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)", "via": "install.sh (dev/demo setup)"}
EOF

echo ""
echo "============================================"
echo " Installation complete."
echo ""
echo " Admin:  admin@marketplace.test / password"
echo " Buyer:  buyer@marketplace.test / password"
echo " Seller: codecraft@marketplace.test / password"
echo ""
echo " NOTE: this script is for local dev/demo setup with fake data."
echo " For a real production first-time setup with your own admin"
echo " account, delete storage/installed.lock and visit /install instead."
echo ""
echo " Start the server:  php artisan serve"
echo "============================================"
