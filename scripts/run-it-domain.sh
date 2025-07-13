#!/bin/bash

# Build the image
docker build -t dv-website .

# Run IT domain container
docker run -d \
  --name it-domain-web-test \
  -p 8080:80 \
  -e DB_HOST=localhost \
  -e DB_NAME=it_domain_db \
  -e DB_USER=it_user \
  -e DB_PASS=it_password \
  dv-website

# Copy IT domain files into the container
docker cp it-domain/. it-domain-web-test:/var/www/html/

echo "IT Domain website should be available at http://localhost:8080"
echo "Container name: it-domain-web-test"