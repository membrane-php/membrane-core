#!/usr/bin/env bash

PWD=$(pwd)

function installComposer() {
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
    then
        >&2 echo 'ERROR: Invalid composer installer signature'
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    return $RESULT
}

function cleanup(){
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine vendor/bin/phpcbf
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine vendor/bin/phpcs
}

function qualityCheck(){
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine vendor/bin/phpstan
}

function test(){
  docker run -ti -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.1-cli-alpine vendor/bin/phpunit -d memory_limit=1G
}

function shell(){
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine /bin/sh
}

case "$1" in

setup)
  [ -d composer.phar ] || installComposer || exit 1;
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine php composer.phar install
  exit 0
  ;;

cleanup)
  cleanup
  exit 0
  ;;

qualityCheck)
  qualityCheck
  exit 0
  ;;

test)
  shift
  test "$@"
  exit 0
  ;;

shell)
  shell
  exit 0
  ;;

*)
  echo "Usage: membrane.sh {cleanup|qualityCheck|test|shell|setup}"
  exit 1
  ;;

esac
