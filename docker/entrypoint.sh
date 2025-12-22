#!/usr/bin/env sh
set -e

cd /var/www/arb

if [ ! -f vendor/autoload.php ]; then
  echo "Installing dependencies..."
  if [ "${APP_ENV:-local}" = "production" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
  else
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

if [ -f .env ]; then
  if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
  fi
fi

if [ "${APP_ENV:-local}" != "production" ]; then
  echo "Running migrations..."
  for attempt in 1 2 3 4 5; do
    if php artisan migrate --force; then
      break
    fi
    echo "Migration attempt ${attempt} failed, retrying in 3s..."
    sleep 3
  done
fi

exec "$@"
