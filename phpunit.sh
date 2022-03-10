#!/bin/sh

if [ $# -eq 1 ]; then
  vendor/bin/phpunit --bootstrap ./Lasa/tests/bootstrap.php --colors $1
  exit 0
fi
vendor/bin/phpunit --bootstrap ./Lasa/tests/bootstrap.php --colors Lasa/tests

