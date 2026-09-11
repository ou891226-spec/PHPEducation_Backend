#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for MySQL..."
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }"; do
  sleep 2
done
echo "MySQL is up."

if [ ! -f .env ]; then
  echo "ERROR: .env is missing. Create it on the host and mount it into the container."
  exit 1
fi

# Host-mounted bootstrap/cache may reference require-dev packages.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-v7.php

if [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

php artisan package:discover --ansi >/dev/null 2>&1 || true
php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

chown -R www-data:www-data storage bootstrap/cache || true

exec docker-php-entrypoint "$@"
