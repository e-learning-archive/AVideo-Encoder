#!/bin/sh

if [ "$#" -ne 2 ]; then
  echo "USAGE: courses.sh <username> <password>"
  exit 1
fi

# syntax: courses.sh <username> <password>
docker exec -t $(docker ps --filter "name=edx" --format "{{.Names}}") edx-dl -u "$1" -p "$2" -i --cache --list-courses 2>&1