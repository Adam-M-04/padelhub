#!/bin/sh
set -e

composer install --no-interaction --prefer-dist
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
