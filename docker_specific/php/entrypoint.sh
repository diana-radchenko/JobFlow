#!/bin/sh
set -e

echo "Optimization started..."

# Ensure database directory exists and is writable
mkdir -p /var/www/database
chmod 775 /var/www/database

# Create SQLite database file if it doesn't exist
if [ ! -f /var/www/database/database.sqlite ]; then
    touch /var/www/database/database.sqlite
    chmod 664 /var/www/database/database.sqlite
fi

php artisan optimize:clear
php artisan optimize

php artisan storage:link || true

echo "Optimization success..."

exec "$@"