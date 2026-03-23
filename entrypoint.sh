#!/usr/bin/env sh

set -e

# generate Doctrine proxies
# su horaro -c 'php vendor/doctrine/orm/bin/doctrine orm:generate-proxies'

# make sure mounted directories have proper permissions
chown horaro:horaro tmp/upload tmp/session log
# nginx runs has the horaro user, needs access to these files
mkdir -p /var/lib/nginx/tmp
chmod -R 666 /var/lib/nginx/tmp/

# hand control over to supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
