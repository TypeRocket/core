#!/usr/bin/env bash
set -euo pipefail

WP_PATH="${WP_PATH:-wordpress}"
PACKAGE_PATH="$(pwd)"
ROOT_PATH="$(cd "$PACKAGE_PATH/../../.." && pwd)"
ROOT_WP_PATH="$ROOT_PATH/$WP_PATH"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-wordpress}"
ALT_DB_NAME="${ALT_DB_NAME:-tr_v51_alt}"

if [ ! -f wp-cli.phar ]; then
    curl -fsSL -o wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi

chmod +x wp-cli.phar

if [ ! -f "$ROOT_PATH/init.php" ] || [ ! -d "$ROOT_PATH/config" ]; then
    tmp_dir="$(mktemp -d)"
    composer create-project typerocket/typerocket "$tmp_dir/typerocket" --prefer-dist --no-dev --no-interaction
    cp "$tmp_dir/typerocket/init.php" "$ROOT_PATH/init.php"
    cp -R "$tmp_dir/typerocket/app" "$ROOT_PATH/app"
    cp -R "$tmp_dir/typerocket/config" "$ROOT_PATH/config"
    cp -R "$tmp_dir/typerocket/storage" "$ROOT_PATH/storage"
    rm -rf "$tmp_dir"
fi

if [ ! -f "$ROOT_WP_PATH/wp-load.php" ]; then
    ./wp-cli.phar core download --allow-root --path="$ROOT_WP_PATH"
fi

if [ ! -f "$ROOT_PATH/wp-config.php" ]; then
    cat > "$ROOT_PATH/wp-config.php" <<PHP
<?php
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASSWORD' );
define( 'DB_HOST', '$DB_HOST' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'AUTH_KEY', 'ci-auth-key' );
define( 'SECURE_AUTH_KEY', 'ci-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'ci-logged-in-key' );
define( 'NONCE_KEY', 'ci-nonce-key' );
define( 'AUTH_SALT', 'ci-auth-salt' );
define( 'SECURE_AUTH_SALT', 'ci-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'ci-logged-in-salt' );
define( 'NONCE_SALT', 'ci-nonce-salt' );

\$table_prefix = 'wp_';

define( 'WP_DEBUG', true );
define( 'TYPEROCKET_ALT_DATABASE_USER', '$DB_USER' );
define( 'TYPEROCKET_ALT_DATABASE_PASSWORD', '$DB_PASSWORD' );
define( 'TYPEROCKET_ALT_DATABASE_DATABASE', '$ALT_DB_NAME' );
define( 'TYPEROCKET_ALT_DATABASE_HOST', '$DB_HOST' );
define( 'TYPEROCKET_LOG_FILE_FOLDER', null );
define( 'TYPEROCKET_GOOGLE_MAPS_API_KEY', '12345678910' );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/wordpress/' );
}

require __DIR__ . '/init.php';
require_once ABSPATH . 'wp-settings.php';
PHP
fi

if ! ./wp-cli.phar core is-installed --allow-root --path="$ROOT_WP_PATH"; then
    ./wp-cli.phar core install \
        --allow-root \
        --admin_name=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --url=http://127.0.0.1 \
        --title=WordPress \
        --path="$ROOT_WP_PATH"
fi
