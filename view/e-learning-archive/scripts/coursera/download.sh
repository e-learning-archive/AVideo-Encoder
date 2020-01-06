#!/bin/bash

# syntax: download.sh <cauth> <course> <section> <lecture>
docker exec -t $(docker ps --filter "name=coursera" --format "{{.Names}}") /app/coursera-dl -ca "$1" --cache-syllabus -f mp4 -sf "$3" -lf "$4" "$2" 2>&1



