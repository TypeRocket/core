#!/bin/bash
set -ev

## ARGS
echo $1
PHPVERSION={$1}

## DATABASE
mysql -e "SET NAMES utf8; create database IF NOT EXISTS typerocket;" -uroot

## APACHE
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi
# enable php-fpm
sudo cp ~/.phpenv/versions/$PHPVERSION/etc/php-fpm.conf.default ~/.phpenv/versions/$PHPVERSION/etc/php-fpm.conf
sudo a2enmod rewrite actions fastcgi alias
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$PHPVERSION/etc/php.ini
~/.phpenv/versions/$PHPVERSION/sbin/php-fpm

# configure apache virtual hosts
sudo cp -f build/travis-ci-apache /etc/apache2/sites-available/default
sudo sed -e "s/%TRAVIS_BUILD_DIR%/$(pwd)/g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart