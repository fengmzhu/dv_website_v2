#!/bin/bash

echo "🔍 Checking database connectivity..."

# Test IT Domain Database
echo "Testing IT Domain Database..."
docker-compose exec -T it-domain-db mysql -u it_user -pit_password -e "SELECT 'IT Domain DB OK' as status;" it_domain_db

# Test NX Domain Database  
echo "Testing NX Domain Database..."
docker-compose exec -T nx-domain-db mysql -u nx_user -pnx_password -e "SELECT 'NX Domain DB OK' as status;" nx_domain_db

# Test web container database connections
echo "Testing web container database connections..."
docker-compose exec -T it-domain-web php /var/www/test-db-connection.php

echo "✅ Database connectivity check complete"