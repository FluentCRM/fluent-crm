#!/bin/bash

counter=1

while true; do
  wp cron event run --due-now --path=/Users/jewel/sites/wp
  echo "Request #$counter completed"
  ((counter++))
  sleep 60
done
