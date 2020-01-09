#!/bin/bash

if [ "$#" -ne 2 ]; then
  echo "USAGE: syllabus.sh <cauth> <course>"
  exit 1
fi

docker exec -t $(docker ps --filter "name=coursera" --format "{{.Names}}") /app/coursera-dl -ca "$1" --cache-syllabus --only-syllabus "$2" 2>&1
