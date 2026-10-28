#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
fi

echo "Esperando a que MySQL esté disponible..."
attempt=0
until php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = getenv("DB_PORT") ?: "3306";
    $database = getenv("DB_DATABASE");
    $username = getenv("DB_USERNAME");
    $password = getenv("DB_PASSWORD");

    try {
        new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    } catch (Throwable $exception) {
        exit(1);
    }
'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "No fue posible conectarse a MySQL después de 120 segundos." >&2
        exit 1
    fi
    sleep 2
done

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan storage:link --force

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
