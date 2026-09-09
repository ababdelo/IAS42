
set -e

APP_DOMAIN="${APP_DOMAIN:-localhost}"
APP_DOMAIN_ALIASES="${APP_DOMAIN_ALIASES:-}"

APP_LOGS_DIR="/var/www/logs/application"
APACHE_LOGS_DIR="/var/www/logs/apache"
APACHE_SSL_CONF="/etc/apache2/sites-available/default-ssl.conf"

mkdir -p "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"
chown -R www-data:www-data "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"
chmod 775 "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"

# Replace the placeholders in the default-ssl.conf file
sed -i "s/__APP_DOMAIN__/${APP_DOMAIN}/g" "$APACHE_SSL_CONF"

if [ -n "$APP_DOMAIN_ALIASES" ]; then
    sed -i "s/__APP_DOMAIN_ALIASES__/ServerAlias ${APP_DOMAIN_ALIASES}/g" "$APACHE_SSL_CONF"
else
    # If no aliases exist, delete the placeholder line
    sed -i "s/__APP_DOMAIN_ALIASES__//g" "$APACHE_SSL_CONF"
fi

echo "Apache SSL virtual host configured for domain: $APP_DOMAIN"

# Final sanity check before starting Apache
if [ ! -f "/etc/apache2/ssl/certs/server.crt" ]; then
    echo "ERROR: SSL Certificates not found in /etc/apache2/ssl/certs/"
    echo "Please ensure you ran 'make build' or 'make ssl' to generate the SSL certificates."
    exit 1
fi

exec apache2-foreground
