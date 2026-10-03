#!/bin/sh
set -e

# Bind-mounted source (development) may not have dependencies installed yet.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

# Generate an application key once and keep it in a volume across restarts.
if [ -z "${APP_KEY:-}" ]; then
    key_file=/var/lib/app/app-key

    if [ ! -s "$key_file" ]; then
        mkdir -p "$(dirname "$key_file")"
        php artisan key:generate --show > "$key_file"
    fi

    APP_KEY="$(cat "$key_file")"
    export APP_KEY
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
fi

php artisan migrate --force

# Seed the demo user and the provided test data only on the first start.
if php artisan tinker --execute 'echo \App\Models\User::query()->exists() ? "seeded" : "empty";' | grep -q empty; then
    php artisan db:seed --force
fi

exec "$@"
