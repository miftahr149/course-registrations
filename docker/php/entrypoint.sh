#!/bin/sh
set -e

# The code is bind-mounted from the host, so the PHP-FPM worker user (www-data)
# may not be allowed to write logs, cache or sessions. Give it these two folders only.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec docker-php-entrypoint "$@"
