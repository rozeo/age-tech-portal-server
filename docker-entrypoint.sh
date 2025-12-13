#!/bin/sh 
# copy from /usr/local/bin/docker-php-entrypoint
set -e

# run migration
php /var/www/apps/sql/migration.php

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- apache2-foreground "$@"
fi

exec "$@"