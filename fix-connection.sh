#!/bin/bash

echo "=== Fixing Database Connection Issues ==="
echo ""

# Option 1: Try the networked approach
echo "Option 1: Restarting with proper network configuration..."
echo "Stopping all containers..."
docker-compose down --remove-orphans

echo "Starting containers with network..."
docker-compose up -d

echo "Waiting for databases to be ready..."
sleep 30

echo "Testing connections..."
docker-compose exec -T it-domain-web php /var/www/test-db-connection.php

echo ""
echo "If the above didn't work, try Option 2:"
echo ""
echo "Option 2: Use localhost connections"
echo "Run: docker-compose -f docker-compose-localhost.yml up -d"
echo ""
echo "Option 3: Direct IP connection"
echo "Edit the database.php files to use container IPs directly:"
echo "- Find container IPs: docker inspect <container-name> | grep IPAddress"
echo "- Update database.php files with the actual IP addresses"
echo ""
echo "Current container status:"
docker-compose ps