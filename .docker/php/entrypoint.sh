#!/bin/sh

# Ensure the vendor directory is writable
chown -R www-data:www-data /var/www/vendor
chmod -R 755 /var/www/vendor

# Execute the original command
exec "$@"