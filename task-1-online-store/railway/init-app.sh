set -e

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --ansi
fi

# Cache configuration for production
php artisan config:cache --ansi
php artisan route:cache --ansi

# Run migrations
php artisan migrate --force --ansi

# Run seeders for initial data
php artisan db:seed --force --ansi