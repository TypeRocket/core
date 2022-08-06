#!/bin/bash
set -ev

## ARGS
PHPVERSION=$1
WORKINGDIR=$2

## INSTALL WORDPRESS
#### http://blog.wppusher.com/continuous-integration-with-wordpress-and-circleci/
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
composer create-project typerocket/typerocket typerocket --prefer-dist --no-dev
./wp-cli.phar core download --allow-root --path=wordpress
./wp-cli.phar core config --allow-root --dbname=wordpress --dbuser=root --dbhost=127.0.0.1 --path=wordpress
./wp-cli.phar core install --allow-root --admin_name=admin --admin_password=admin --admin_email=admin@example.com --url=http://127.0.0.1 --title=WordPress --path=wordpress
./wp-cli.phar wp config set TYPEROCKET_ALT_DATABASE_USER root --add
./wp-cli.phar wp config set TYPEROCKET_ALT_DATABASE_PASSWORD "''" --add --raw
./wp-cli.phar wp config set TYPEROCKET_ALT_DATABASE_DATABASE tr_v51_alt --add
./wp-cli.phar wp config set TYPEROCKET_ALT_DATABASE_HOST localhost --add