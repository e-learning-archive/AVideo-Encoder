#!/bin/sh

if [ "$#" -ne 4 ]; then
  echo "USAGE: download.sh <username> <password> <course_url> <section_number> "
  exit 1
fi

docker exec -t $(docker ps --filter "name=edx" --format "{{.Names}}") edx-dl -u "$1" -p "$2" --output-dir "/Downloaded/" --ignore-errors --cache --filter-section $4 "$3" 2>&1