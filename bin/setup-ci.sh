#!/usr/bin/env bash
set -euo pipefail

WP_PATH="${WP_PATH:-wordpress}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-wordpress}"
ALT_DB_NAME="${ALT_DB_NAME:-tr_v51_alt}"

if [ ! -f wp-cli.phar ]; then
    curl -fsSL -o wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi

chmod +x wp-cli.phar

if [ ! -d typerocket ]; then
    composer create-project typerocket/typerocket typerocket --prefer-dist --no-dev --no-interaction
fi

if [ ! -f "$WP_PATH/wp-load.php" ]; then
    ./wp-cli.phar core download --allow-root --path="$WP_PATH"
fi

if [ ! -f "$WP_PATH/wp-config.php" ]; then
    ./wp-cli.phar core config \
        --allow-root \
        --dbname="$DB_NAME" \
        --dbuser="$DB_USER" \
        --dbpass="$DB_PASSWORD" \
        --dbhost="$DB_HOST" \
        --path="$WP_PATH"
fi

if ! ./wp-cli.phar core is-installed --allow-root --path="$WP_PATH"; then
    ./wp-cli.phar core install \
        --allow-root \
        --admin_name=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --url=http://127.0.0.1 \
        --title=WordPress \
        --path="$WP_PATH"
fi

./wp-cli.phar config set TYPEROCKET_ALT_DATABASE_USER "$DB_USER" --add --allow-root --path="$WP_PATH"
./wp-cli.phar config set TYPEROCKET_ALT_DATABASE_PASSWORD "$DB_PASSWORD" --add --allow-root --path="$WP_PATH"
./wp-cli.phar config set TYPEROCKET_ALT_DATABASE_DATABASE "$ALT_DB_NAME" --add --allow-root --path="$WP_PATH"
./wp-cli.phar config set TYPEROCKET_ALT_DATABASE_HOST "$DB_HOST" --add --allow-root --path="$WP_PATH"
