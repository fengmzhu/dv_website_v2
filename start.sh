#!/bin/bash

echo "Starting DV Website LAMP Stack..."

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    echo "Error: Docker is not installed. Please install Docker and Docker Compose first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "Error: Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Stop any existing containers
echo "Stopping existing containers..."
docker-compose down

# Build and start containers
echo "Building and starting containers..."
docker-compose up --build -d

# Wait for databases to initialize
echo "Waiting for databases to initialize..."
sleep 30

# Test database connections
echo "Testing database connections..."
docker-compose exec -T it-domain-web php -f /var/www/html/../test-db-connection.php

# Check container status
echo "Checking container status..."
docker-compose ps

echo ""
echo "=== DV Website is now running ==="
echo ""
echo "IT Domain Website: http://localhost:8080"
echo "  - Use this for data entry and management"
echo "  - Export data to CSV for NX domain import"
echo ""
echo "NX Domain Website: http://localhost:8081" 
echo "  - Use this for viewing DV reports and TO summary"
echo "  - Import CSV files from IT domain"
echo ""
echo "MySQL Databases:"
echo "  - IT Domain: localhost:3306 (user: it_user, password: it_password)"
echo "  - NX Domain: localhost:3307 (user: nx_user, password: nx_password)"
echo ""
echo "To stop: docker-compose down"
echo "To view logs: docker-compose logs -f"
echo ""