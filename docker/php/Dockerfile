# PHP-FPM image for LOCAL DEVELOPMENT. The source code is bind-mounted by compose.yaml.
# A production image (code copied in, multi-stage, tagged with the Git SHA)
# comes later, in the Packaging stage.
FROM php:8.5-fpm-alpine

# Helper that installs PHP extensions with their system libraries.
# https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# pdo_mysql: MySQL driver. bcmath: exact decimal math (GPA).
# intl, zip: commonly needed by Laravel packages.
RUN install-php-extensions pdo_mysql bcmath intl zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
