#!/usr/bin/env sh

set -e

# generate Doctrine proxies
# su horaro -c 'php vendor/doctrine/orm/bin/doctrine orm:generate-proxies'

# make sure mounted directories have proper permissions
chown horaro:horaro tmp/upload tmp/session log
# nginx runs has the horaro user
mkdir -p /var/lib/nginx/tmp/client_body
chown -R horaro:horaro /var/lib/nginx/tmp/client_body

# hand control over to supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
