####################################################################################
#                         Author: Abderrahmane Abdelouafi                          #
#                          File Name: docker-entrypoint.sh                         #
#                      Creation Date: October 04, 2025 14:01 PM                    #
#                      Last Updated: Decemeber 24, 2025 09:50 AM                   #
#                           Source Language: shellscript                           #
#                                                                                  #
#                             --- Code Description ---                             #
#           Docker entrypoint script for Apache container initialization.          #
#    It sets up the environment, validates SSL certificates, and starts Apache.    #
#  The script ensures that the necessary directories and permissions are in place. #
# ================================================================================ #

#!/bin/sh

set -eu

APP_DOMAIN="${APP_DOMAIN:-localhost}"
APP_DOMAIN_ALIASES="${APP_DOMAIN_ALIASES:-}"
CONTAINER_ROLE="${CONTAINER_ROLE:-web}"

APP_LOGS_DIR="/var/www/logs/application"
APACHE_LOGS_DIR="/var/www/logs/apache"
APACHE_SSL_TEMPLATE="/etc/apache2/sites-available/default-ssl.conf.template"
APACHE_SSL_CONF="/etc/apache2/sites-available/default-ssl.conf"

SSL_DIR="/etc/apache2/ssl"
SSL_CERT_DIR="${SSL_DIR}/certs"
SSL_PRIVATE_DIR="${SSL_DIR}/private"

SERVER_CERT="${SSL_CERT_DIR}/server.crt"
CA_BUNDLE="${SSL_CERT_DIR}/ca_bundle.crt"
FULLCHAIN="${SSL_CERT_DIR}/fullchain.pem"
PRIVATE_KEY="${SSL_PRIVATE_DIR}/server.key"

mkdir -p "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"

chown -R www-data:www-data "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"
chmod 775 "$APP_LOGS_DIR" "$APACHE_LOGS_DIR"

if [ "$CONTAINER_ROLE" = "bridge" ]; then
    exec php /var/www/html/backend/mqtt/bridge.php
fi

if [ -z "$APP_DOMAIN" ]; then
    echo "ERROR: APP_DOMAIN is empty."
    exit 1
fi

case "$APP_DOMAIN" in
    *[!A-Za-z0-9.-]*)
        echo "ERROR: APP_DOMAIN contains invalid characters."
        exit 1
        ;;
esac

echo "Validating IAS42 SSL deployment files..."

if [ ! -f "$APACHE_SSL_TEMPLATE" ]; then
    echo "ERROR: Apache SSL template not found: $APACHE_SSL_TEMPLATE"
    exit 1
fi

if [ ! -s "$SERVER_CERT" ]; then
    echo "ERROR: Server certificate not found or empty: $SERVER_CERT"
    exit 1
fi

if [ ! -s "$PRIVATE_KEY" ]; then
    echo "ERROR: Private key not found or empty: $PRIVATE_KEY"
    exit 1
fi

if [ ! -s "$CA_BUNDLE" ]; then
    echo "ERROR: CA bundle not found or empty: $CA_BUNDLE"
    exit 1
fi

if [ ! -s "$FULLCHAIN" ]; then
    echo "ERROR: Fullchain certificate not found or empty: $FULLCHAIN"
    exit 1
fi

echo "Checking certificate..."

if ! openssl x509 -in "$SERVER_CERT" -noout >/dev/null 2>&1; then
    echo "ERROR: Invalid server certificate: $SERVER_CERT"
    exit 1
fi

if ! openssl rsa -in "$PRIVATE_KEY" -check -noout >/dev/null 2>&1; then
    echo "ERROR: Invalid RSA private key: $PRIVATE_KEY"
    exit 1
fi

if ! openssl x509 -in "$FULLCHAIN" -noout >/dev/null 2>&1; then
    echo "ERROR: Invalid fullchain certificate: $FULLCHAIN"
    exit 1
fi

if ! openssl x509 -checkend 0 -noout -in "$SERVER_CERT" >/dev/null 2>&1; then
    echo "ERROR: Server certificate has expired."
    exit 1
fi

if ! openssl x509 -in "$SERVER_CERT" -noout -checkhost "$APP_DOMAIN" >/dev/null 2>&1; then
    echo "ERROR: Server certificate does not cover APP_DOMAIN=$APP_DOMAIN"
    exit 1
fi

echo "Certificate is valid for $APP_DOMAIN"

echo "Checking certificate/private-key match..."

CERT_MODULUS_HASH="$(
    openssl x509 \
        -in "$SERVER_CERT" \
        -noout \
        -modulus 2>/dev/null |
    openssl sha256 2>/dev/null |
    awk '{print $2}'
)"

KEY_MODULUS_HASH="$(
    openssl rsa \
        -in "$PRIVATE_KEY" \
        -noout \
        -modulus 2>/dev/null |
    openssl sha256 2>/dev/null |
    awk '{print $2}'
)"

if [ -z "$CERT_MODULUS_HASH" ] || [ -z "$KEY_MODULUS_HASH" ]; then
    echo "ERROR: Unable to determine certificate/private-key modulus."
    exit 1
fi

if [ "$CERT_MODULUS_HASH" != "$KEY_MODULUS_HASH" ]; then
    echo "ERROR: Server certificate does NOT match private key."
    exit 1
fi

echo "Certificate matches private key."

echo "Checking certificate chain..."

if ! openssl verify \
    -CAfile "$CA_BUNDLE" \
    "$SERVER_CERT" >/dev/null 2>&1; then
    echo "ERROR: Server certificate chain verification failed."
    exit 1
fi

echo "Certificate chain is valid."

TMP_CONF="${APACHE_SSL_CONF}.tmp"

sed \
    -e "s|__APP_DOMAIN__|${APP_DOMAIN}|g" \
    -e "s|__APP_DOMAIN_ALIASES__|$( [ -n "$APP_DOMAIN_ALIASES" ] && printf '%s' "ServerAlias $APP_DOMAIN_ALIASES" || true )|g" \
    "$APACHE_SSL_TEMPLATE" > "$TMP_CONF"

mv "$TMP_CONF" "$APACHE_SSL_CONF"

if ! apache2ctl configtest; then
    echo "ERROR: Apache configuration test failed."
    exit 1
fi

exec apache2-foreground
