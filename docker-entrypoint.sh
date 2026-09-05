#!/bin/bash

set -e

PORT="${PORT:-10000}"

echo "Starting Apache on port $PORT"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "Apache configuration:"
grep -R "Listen\|VirtualHost" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
