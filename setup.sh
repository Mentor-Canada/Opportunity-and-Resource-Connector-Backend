#!/bin/bash
set -ex

mkdir -p web/sites/default/files
sudo chmod -R 777 web/sites/default/files
docker-compose exec db mysql -e "DROP DATABASE IF EXISTS connector; CREATE DATABASE connector;"
docker-compose exec -T db mysql connector < connector.sql
docker-compose exec -u 1000 web composer install
docker-compose exec -u 1000 web /bin/bash ./patches/patch.sh
docker-compose exec -u 1000 web ./vendor/bin/drush cr
