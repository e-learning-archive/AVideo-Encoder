#!/bin/bash

source ../.env

cd "$DOCKER_COMPOSE"
eval $(docker-machine env default)
docker-compose run --rm coursera -ca "$1" --cache-syllabus --only-syllabus "$2"