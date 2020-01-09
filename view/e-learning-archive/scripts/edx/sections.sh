#!/bin/sh

if [ "$#" -ne 3 ]; then
  echo "USAGE: sections.sh <username> <password> <course_url>"
  exit 1
fi


docker exec -t $(docker ps --filter "name=edx" --format "{{.Names}}") edx-dl -u "$1" -p "$2" -i --cache --list-sections "$3" 2>&1