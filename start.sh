#!/bin/bash
set -e

# Arranca la API de galeria (Node.js) en segundo plano
cd /var/www/html/galeria-api
node server.js &

# Genera el crontab de keepalive con la URL publica fija (RENDER_EXTERNAL_URL no llega seteada en este servicio)
KEEPALIVE_URL="https://app-pantera.onrender.com"
mkdir -p /etc/cron.d
echo "*/10 * * * * root curl -s -o /dev/null $KEEPALIVE_URL/ >> /var/log/keepalive.log 2>&1" > /etc/cron.d/keepalive
chmod 0644 /etc/cron.d/keepalive
crontab /etc/cron.d/keepalive
echo "* * * * * root curl -s -o /dev/null ${KEEPALIVE_URL}/view/home/cron_notificaciones.php?token=${CRON_NOTIF_TOKEN} >> /var/log/cron_notif.log 2>&1" >> /etc/cron.d/keepalive
crontab /etc/cron.d/keepalive

# Arranca cron en segundo plano (keepalive)
cron

# Arranca el tunel cliente de Cloudflare Access hacia la base de datos (pantera_4ki5 en el servidor lince)
cloudflared access tcp \
  --hostname pantera-db.mudanzasellince.com \
  --url 127.0.0.1:5433 \
  --service-token-id "$CF_SERVICE_TOKEN_ID" \
  --service-token-secret "$CF_SERVICE_TOKEN_SECRET" &

sleep 3  # dar tiempo a que el tunel levante antes de que la app intente conectar

# Arranca Apache (PHP) en primer plano - proceso principal del contenedor
cd /var/www/html
apache2-foreground
