#!/bin/bash

# Run NX domain container
docker run -d \
  --name nx-domain-web-test \
  -p 8081:80 \
  -e DB_HOST=localhost \
  -e DB_NAME=nx_domain_db \
  -e DB_USER=nx_user \
  -e DB_PASS=nx_password \
  dv-website

# Copy NX domain files into the container
docker cp nx-domain/. nx-domain-web-test:/var/www/html/

echo "NX Domain website should be available at http://localhost:8081"
echo "Container name: nx-domain-web-test"