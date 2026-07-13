#!/bin/sh
set -eu

mkdir -p /app/runtime /tmp/caddy
chown -R www-data:www-data /app/runtime /tmp/caddy

exec runuser -u www-data -- "$@"
