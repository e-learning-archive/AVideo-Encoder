#!/bin/bash

# syntax: syllabus.sh <cauth> <course>
docker exec -t $(docker ps --filter "name=coursera" --format "{{.Names}}") /app/coursera-dl -ca "$1" --cache-syllabus --only-syllabus "$2" 2>&1
