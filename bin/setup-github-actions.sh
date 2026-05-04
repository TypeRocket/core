#!/usr/bin/env bash
set -euo pipefail

ROOT_PATH="$(cd "$(dirname "$0")/../../../.." && pwd)"
CORE_PATH="$ROOT_PATH/vendor/typerocket/core"
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
ALT_DB_NAME="${ALT_DB_NAME:-tr_v51_alt}"
DB_HOST="${DB_HOST:-127.0.0.1}"

cd "$ROOT_PATH"

php galaxy root:install "$DB_NAME" "$DB_USER" "$DB_PASSWORD"

php galaxy config:seed

if ! grep -q "TYPEROCKET_ALT_DATABASE_DATABASE" wp-config.php; then
    sed -i "/\/\* That's all, stop editing! Happy publishing\. \*\//i\\
define( 'TYPEROCKET_ALT_DATABASE_USER', '$DB_USER' );\\
define( 'TYPEROCKET_ALT_DATABASE_PASSWORD', '$DB_PASSWORD' );\\
define( 'TYPEROCKET_ALT_DATABASE_DATABASE', '$ALT_DB_NAME' );\\
define( 'TYPEROCKET_ALT_DATABASE_HOST', '$DB_HOST' );\\
define( 'TYPEROCKET_LOG_FILE_FOLDER', null );\\
define( 'TYPEROCKET_GOOGLE_MAPS_API_KEY', '12345678910' );\\
" wp-config.php
fi

cd "$CORE_PATH"
composer install --no-interaction --prefer-dist
