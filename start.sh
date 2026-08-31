#!/bin/bash
set -e

# Arranca la API de galeria (Node.js) en segundo plano
cd /var/www/html/galeria-api
node server.js &

# Genera el crontab de keepalive con la URL real que Render asigna en runtime
KEEPALIVE_URL=${RENDER_EXTERNAL_URL:-http://127.0.0.1}
mkdir -p /etc/cron.d
echo "*/10 * * * * curl -s -o /dev/null $KEEPALIVE_URL/ >> /var/log/keepalive.log 2>&1" > /etc/cron.d/keepalive
echo "" >> /etc/cron.d/keepalive
chmod 0644 /etc/cron.d/keepalive
crontab /etc/cron.d/keepalive

# Arranca cron en segundo plano (keepalive)
cron

# Arranca Apache (PHP) en primer plano - proceso principal del contenedor
cd /var/www/html
apache2-foreground
