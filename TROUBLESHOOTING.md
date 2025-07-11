# Troubleshooting Database Connection Issues

## Problem: "getaddrinfo for nx-domain-db failed"

This error means Docker containers can't resolve each other's hostnames.

## Solutions

### Solution 1: Restart with Network Configuration (Recommended)
```bash
cd /workspace/dv_website
./fix-connection.sh
```

### Solution 2: Use Flexible Database Configuration
Replace the database.php file:
```bash
cp nx-domain/config/database-flexible.php nx-domain/config/database.php
cp nx-domain/config/database-flexible.php it-domain/config/database.php
```

### Solution 3: Use Direct IP Addresses
1. Find container IP addresses:
```bash
docker inspect nx-domain-mysql | grep IPAddress
docker inspect it-domain-mysql | grep IPAddress
```

2. Edit database.php files and replace hostnames with IP addresses:
```php
$this->host = '172.17.0.3'; // Use actual IP from inspect command
```

### Solution 4: Use Host Networking (Development Only)
Edit docker-compose.yml and add to each service:
```yaml
network_mode: "host"
```

Then use localhost with different ports.

### Solution 5: Check Docker DNS
```bash
# Check if Docker daemon is running
docker info

# Check network configuration
docker network ls
docker network inspect bridge

# Recreate network
docker-compose down
docker network prune
docker-compose up -d
```

## Common Causes

1. **Container not on same network** - Fixed by adding custom network in docker-compose.yml
2. **DNS resolution issues** - Common in some Docker installations
3. **Container startup order** - Database might not be ready when web container starts
4. **Firewall/Security software** - May block inter-container communication

## Quick Diagnostics

Run these commands to diagnose:
```bash
# Check all containers are running
docker-compose ps

# Test database containers directly
docker exec -it nx-domain-mysql mysql -u nx_user -pnx_password -e "SELECT 1"
docker exec -it it-domain-mysql mysql -u it_user -pit_password -e "SELECT 1"

# Check network connectivity from web container
docker exec -it nx-domain-web ping -c 1 nx-domain-db
docker exec -it nx-domain-web nslookup nx-domain-db

# View container logs
docker-compose logs nx-domain-db
docker-compose logs nx-domain-web
```

## Emergency Workaround

If nothing else works, use this single-container setup:
```bash
docker-compose -f docker-compose-localhost.yml up -d
```

This runs everything in a single container with localhost connections.