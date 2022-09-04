#!/usr/bin/env bash

PWD=$(pwd)

function test(){
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine vendor/bin/phpunit
}

function shell(){
  docker run -ti -u $(id -u):$(id -g)  -v "$PWD":/app -w /app php:8.1-cli-alpine /bin/sh
}

case "$1" in

shell)
  shell
  exit 0
  ;;

test)
  shift
  test "$@"
  exit 0
  ;;

*)
  echo "Usage: membrane.sh {test}"
  exit 1
  ;;

esac