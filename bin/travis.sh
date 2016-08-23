#!/bin/bash
set -ev

## ARGS
PHPVERSION=$1
WORKINGDIR=$2

## DATABASE
mysql -e "SET NAMES utf8; create database IF NOT EXISTS wordpress;" -uroot

## INSTALL WORDPRESS
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
./wp-cli.phar core download --allow-root --path=wordpress
./wp-cli.phar core config --allow-root --dbname=wordpress --dbuser=travis --dbhost=127.0.0.1 --path=wordpress
./wp-cli.phar core install --allow-root --admin_name=admin --admin_password=admin --admin_email=admin@example.com --url=http://127.0.0.1 --title=WordPress --path=wordpress

## APACHE
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi
# enable php-fpm
sudo cp ~/.phpenv/versions/$PHPVERSION/etc/php-fpm.conf.default ~/.phpenv/versions/$PHPVERSION/etc/php-fpm.conf
sudo a2enmod rewrite actions fastcgi alias
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$PHPVERSION/etc/php.ini
~/.phpenv/versions/$PHPVERSION/sbin/php-fpm

# configure apache virtual hosts
sudo cp -f bin/travis-ci-apache /etc/apache2/sites-available/default
sudo sed -i "s|%TRAVIS_BUILD_DIR%|$WORKINGDIR|g" /etc/apache2/sites-available/default
sudo service apache2 restart