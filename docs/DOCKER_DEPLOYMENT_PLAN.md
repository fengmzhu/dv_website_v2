# Docker Deployment Plan for DV Website on AWS EC2 (Ubuntu)

## Overview
- **Domain**: fengmzhu.men
- **Path-based routing**: 
  - `fengmzhu.men/it_website` → IT Domain application
  - `fengmzhu.men/nx_website` → NX Domain application
- **OS**: Ubuntu 22.04 LTS on AWS EC2
- **Architecture**: Docker containers with Nginx reverse proxy

## Architecture Overview
- **Two separate PHP-Apache containers** for IT and NX applications
- **Shared MySQL container** with dual databases (it_domain_db, nx_domain_db)
- **Nginx reverse proxy container** for path-based routing and SSL termination
- **Docker Compose** for orchestration and management

## Container Structure

### 1. MySQL Database Container
```yaml
mysql:
  image: mysql:8.0
  ports: ["3306:3306", "3307:3306"] # Both ports for compatibility
  volumes:
    - mysql_data:/var/lib/mysql
    - ./database:/docker-entrypoint-initdb.d
  environment:
    - MYSQL_ROOT_PASSWORD=your_secure_password
    - MYSQL_CHARACTER_SET_SERVER=utf8mb4
    - MYSQL_COLLATION_SERVER=utf8mb4_unicode_ci
```

### 2. IT Domain Website Container
```yaml
it-domain:
  build: ./it-domain
  volumes:
    - ./it-domain:/var/www/html
  environment:
    - DB_HOST=mysql
    - DB_PORT=3306
    - DB_NAME=it_domain_db
    - BASE_PATH=/it_website
```

### 3. NX Domain Website Container
```yaml
nx-domain:
  build: ./nx-domain
  volumes:
    - ./nx-domain:/var/www/html
  environment:
    - DB_HOST=mysql
    - DB_PORT=3306
    - DB_NAME=nx_domain_db
    - BASE_PATH=/nx_website
```

### 4. Nginx Reverse Proxy Container
```yaml
nginx:
  image: nginx:alpine
  ports: ["80:80", "443:443"]
  volumes:
    - ./nginx/conf.d:/etc/nginx/conf.d
    - ./ssl:/etc/ssl/certs
  depends_on: [it-domain, nx-domain]
```

## EC2 Instance Requirements (Ubuntu)

### Instance Specifications
- **Type**: t3.medium (2 vCPU, 4GB RAM) minimum
- **Storage**: 30GB EBS volume (gp3)
- **OS**: Ubuntu 22.04 LTS
- **DNS**: A record pointing fengmzhu.men to EC2 public IP

### Security Group Configuration
```bash
# Web traffic
HTTP (80) - 0.0.0.0/0
HTTPS (443) - 0.0.0.0/0

# SSH access (restrict to your IP)
SSH (22) - YOUR_IP/32

# MySQL (internal container communication only)
MySQL (3306, 3307) - Internal Docker network only
```

## Directory Structure on Ubuntu EC2
```
/opt/dv-website/
├── docker-compose.yml
├── .env
├── it-domain/
│   ├── Dockerfile
│   └── [IT domain website files]
├── nx-domain/
│   ├── Dockerfile
│   └── [NX domain website files]
├── database/
│   ├── it-domain-schema.sql
│   └── nx-domain-schema.sql
├── nginx/
│   └── conf.d/
│       └── default.conf
└── ssl/
    ├── fengmzhu.men.crt
    └── fengmzhu.men.key
```

## Nginx Configuration for Path-Based Routing

### Single Domain Configuration (`nginx/conf.d/default.conf`)
```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name fengmzhu.men www.fengmzhu.men;
    return 301 https://fengmzhu.men$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name fengmzhu.men www.fengmzhu.men;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/certs/fengmzhu.men.crt;
    ssl_certificate_key /etc/ssl/certs/fengmzhu.men.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Root location (optional landing page)
    location / {
        return 200 '<!DOCTYPE html>
<html>
<head><title>DV Management System</title></head>
<body>
<h1>DV Management System</h1>
<p><a href="/it_website/">IT Domain Website</a></p>
<p><a href="/nx_website/">NX Domain Website</a></p>
</body>
</html>';
        add_header Content-Type text/html;
    }
    
    # IT Domain Website
    location /it_website/ {
        proxy_pass http://it-domain/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Script-Name /it_website;
        
        # Handle PHP redirects properly
        proxy_redirect / /it_website/;
    }
    
    # NX Domain Website  
    location /nx_website/ {
        proxy_pass http://nx-domain/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Script-Name /nx_website;
        
        # Handle PHP redirects properly
        proxy_redirect / /nx_website/;
    }
}
```

## Docker Compose Configuration (`docker-compose.yml`)
```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: dv-mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
      - "3307:3306"  # Secondary port for compatibility
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database:/docker-entrypoint-initdb.d:ro
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_CHARACTER_SET_SERVER: utf8mb4
      MYSQL_COLLATION_SERVER: utf8mb4_unicode_ci
    networks:
      - dv-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  it-domain:
    build: 
      context: ./it-domain
      dockerfile: Dockerfile
    container_name: dv-it-domain
    restart: unless-stopped
    volumes:
      - ./it-domain:/var/www/html
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_NAME: it_domain_db
      DB_USER: ${DB_USER}
      DB_PASSWORD: ${DB_PASSWORD}
      BASE_PATH: /it_website
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - dv-network

  nx-domain:
    build:
      context: ./nx-domain
      dockerfile: Dockerfile
    container_name: dv-nx-domain
    restart: unless-stopped
    volumes:
      - ./nx-domain:/var/www/html
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_NAME: nx_domain_db
      DB_USER: ${DB_USER}
      DB_PASSWORD: ${DB_PASSWORD}
      BASE_PATH: /nx_website
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - dv-network

  nginx:
    image: nginx:alpine
    container_name: dv-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - ./ssl:/etc/ssl/certs:ro
    depends_on:
      - it-domain
      - nx-domain
    networks:
      - dv-network

volumes:
  mysql_data:
    driver: local

networks:
  dv-network:
    driver: bridge
```

## Dockerfile for PHP Applications

### IT Domain Dockerfile (`it-domain/Dockerfile`)
```dockerfile
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Configure Apache for subdirectory
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
```

### NX Domain Dockerfile (`nx-domain/Dockerfile`)
```dockerfile
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Configure Apache for subdirectory
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
```

## Environment Configuration (`.env`)
```bash
# Database Configuration
MYSQL_ROOT_PASSWORD=your_very_secure_root_password_here
DB_USER=dv_user
DB_PASSWORD=your_secure_db_password_here

# Application Configuration
DOMAIN=fengmzhu.men
IT_PATH=/it_website
NX_PATH=/nx_website

# SSL Configuration
SSL_EMAIL=your-email@example.com
```

## SSL Certificate Setup with Let's Encrypt

### Install Certbot on Ubuntu
```bash
sudo apt update
sudo apt install snapd
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
```

### Generate SSL Certificate
```bash
# Stop nginx temporarily
sudo docker-compose stop nginx

# Generate certificate
sudo certbot certonly --standalone -d fengmzhu.men -d www.fengmzhu.men

# Copy certificates to project directory
sudo cp /etc/letsencrypt/live/fengmzhu.men/fullchain.pem /opt/dv-website/ssl/fengmzhu.men.crt
sudo cp /etc/letsencrypt/live/fengmzhu.men/privkey.pem /opt/dv-website/ssl/fengmzhu.men.key
sudo chown ubuntu:ubuntu /opt/dv-website/ssl/*

# Restart nginx
sudo docker-compose start nginx
```

### Auto-renewal Setup
```bash
# Add to crontab
echo "0 12 * * * /usr/bin/certbot renew --quiet && /usr/bin/docker-compose -f /opt/dv-website/docker-compose.yml restart nginx" | sudo crontab -
```

## Deployment Steps on Ubuntu EC2

### 1. Initial Server Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker ubuntu
newgrp docker

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

### 2. Setup Application Directory
```bash
# Create application directory
sudo mkdir -p /opt/dv-website
sudo chown ubuntu:ubuntu /opt/dv-website
cd /opt/dv-website

# Create directory structure
mkdir -p {it-domain,nx-domain,database,nginx/conf.d,ssl}
```

### 3. Transfer Files from Development
```bash
# From your local machine, copy files to EC2
scp -r /workspace/dv_website/it-domain/* ubuntu@your-ec2-ip:/opt/dv-website/it-domain/
scp -r /workspace/dv_website/nx-domain/* ubuntu@your-ec2-ip:/opt/dv-website/nx-domain/
scp -r /workspace/dv_website/database/* ubuntu@your-ec2-ip:/opt/dv-website/database/

# Or use git
git clone your-repo-url /opt/dv-website
```

### 4. Configure Environment
```bash
# Create environment file
cp .env.example .env
nano .env  # Edit with your values

# Create nginx configuration
nano nginx/conf.d/default.conf  # Copy the nginx config above

# Create Dockerfiles
nano it-domain/Dockerfile  # Copy Dockerfile content above
nano nx-domain/Dockerfile  # Copy Dockerfile content above
```

### 5. DNS Configuration
- Point your domain `fengmzhu.men` A record to your EC2 public IP
- Optionally add `www.fengmzhu.men` CNAME to `fengmzhu.men`

### 6. Deploy Application
```bash
# Build and start containers
docker-compose up -d

# Check container status
docker-compose ps

# View logs
docker-compose logs -f
```

### 7. Verify Deployment
- Visit `https://fengmzhu.men/it_website/` for IT Domain
- Visit `https://fengmzhu.men/nx_website/` for NX Domain
- Check SSL certificate is working properly

## Application Code Modifications Required

### For Path-Based Routing Support
Both applications need minor modifications to work with path prefixes:

#### IT Domain PHP Configuration
```php
// Add to config files
$base_path = $_SERVER['HTTP_X_SCRIPT_NAME'] ?? '';
$base_url = 'https://' . $_SERVER['HTTP_HOST'] . $base_path;

// Update all internal links and form actions
<form action="<?php echo $base_path; ?>/api/endpoint.php">
<a href="<?php echo $base_path; ?>/page.php">
```

#### NX Domain PHP Configuration
```php
// Same modifications as IT domain
$base_path = $_SERVER['HTTP_X_SCRIPT_NAME'] ?? '';
$base_url = 'https://' . $_SERVER['HTTP_HOST'] . $base_path;
```

## Monitoring and Maintenance

### Container Health Monitoring
```bash
# Check all container status
docker-compose ps

# View real-time logs
docker-compose logs -f mysql
docker-compose logs -f it-domain
docker-compose logs -f nx-domain
docker-compose logs -f nginx

# Restart specific service
docker-compose restart mysql
```

### Database Backup Script (`backup.sh`)
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup databases
docker-compose exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} it_domain_db > $BACKUP_DIR/it_domain_backup_$DATE.sql
docker-compose exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} nx_domain_db > $BACKUP_DIR/nx_domain_backup_$DATE.sql

# Compress backups
tar -czf $BACKUP_DIR/dv_backup_$DATE.tar.gz $BACKUP_DIR/*_$DATE.sql

# Remove individual SQL files
rm $BACKUP_DIR/*_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "dv_backup_*.tar.gz" -mtime +7 -delete

echo "Backup completed: dv_backup_$DATE.tar.gz"
```

### System Updates
```bash
# Update Docker images
docker-compose pull
docker-compose up -d

# Update application code
git pull origin main
docker-compose restart it-domain nx-domain
```

## Security Considerations

### Firewall Configuration (UFW)
```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw deny 3306
sudo ufw deny 3307
```

### Container Security
- Run containers as non-root users where possible
- Use specific Docker image tags instead of `latest`
- Regularly update base images
- Implement proper secrets management
- Use Docker secrets for sensitive data

### Database Security
- Use strong passwords
- Limit database user privileges
- Enable MySQL audit logging
- Regular security updates

## Troubleshooting

### Common Issues
1. **SSL Certificate Issues**: Check certificate paths and permissions
2. **Database Connection**: Verify MySQL container is healthy
3. **Path Routing**: Check nginx configuration and application base paths
4. **Port Conflicts**: Ensure ports 80/443 are not used by other services

### Debug Commands
```bash
# Check container networking
docker network ls
docker network inspect dv-website_dv-network

# Access container shell
docker-compose exec mysql bash
docker-compose exec it-domain bash

# Check nginx configuration
docker-compose exec nginx nginx -t

# View detailed logs
docker-compose logs --tail=100 service-name
```

This deployment plan provides a robust, scalable solution for hosting your dual-domain DV website system on AWS EC2 using Docker containers with path-based routing.