#!/bin/bash

# DV Website Docker Deployment Script for Ubuntu EC2
# This script automates the entire deployment process

set -e  # Exit on any error

echo "🚀 Starting DV Website Docker Deployment..."

# Configuration
DOMAIN="fengmzhu.men"
PROJECT_DIR="/opt/dv-website"
EMAIL="your-email@example.com"  # Change this to your email

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root"
   exit 1
fi

# Step 1: Update system and install Docker
install_docker() {
    print_status "Installing Docker and Docker Compose..."
    
    # Update system
    sudo apt update && sudo apt upgrade -y
    
    # Install Docker
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker ubuntu
    rm get-docker.sh
    
    # Install Docker Compose
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    # Apply docker group membership without logout
    newgrp docker <<EOF
echo "Docker installed successfully"
EOF
    
    print_status "Docker installation completed"
}

# Step 2: Setup project directory
setup_directories() {
    print_status "Setting up project directories..."
    
    # Create main directory
    sudo mkdir -p $PROJECT_DIR
    sudo chown ubuntu:ubuntu $PROJECT_DIR
    cd $PROJECT_DIR
    
    # Create subdirectories
    mkdir -p {it-domain,nx-domain,database,nginx/conf.d,ssl,backups}
    
    print_status "Directory structure created"
}

# Step 3: Generate Docker configurations
create_docker_configs() {
    print_status "Creating Docker configuration files..."
    
    # Create docker-compose.yml
    cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: dv-mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
      - "3307:3306"
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
EOF

    # Create .env file
    cat > .env << EOF
# Database Configuration
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32)
DB_USER=dv_user
DB_PASSWORD=$(openssl rand -base64 24)

# Application Configuration
DOMAIN=$DOMAIN
IT_PATH=/it_website
NX_PATH=/nx_website
EOF

    print_status "Docker configuration files created"
}

# Step 4: Create Dockerfiles
create_dockerfiles() {
    print_status "Creating Dockerfiles for applications..."
    
    # IT Domain Dockerfile
    cat > it-domain/Dockerfile << 'EOF'
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Configure Apache
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
EOF

    # NX Domain Dockerfile
    cat > nx-domain/Dockerfile << 'EOF'
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Configure Apache
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
EOF

    print_status "Dockerfiles created"
}

# Step 5: Create Nginx configuration
create_nginx_config() {
    print_status "Creating Nginx configuration..."
    
    cat > nginx/conf.d/default.conf << 'EOF'
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
    
    # Root location
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
        proxy_redirect / /nx_website/;
    }
}
EOF

    print_status "Nginx configuration created"
}

# Step 6: Install SSL certificates
setup_ssl() {
    print_status "Setting up SSL certificates..."
    
    # Install certbot
    sudo apt update
    sudo apt install snapd -y
    sudo snap install core
    sudo snap refresh core
    sudo snap install --classic certbot
    sudo ln -s /snap/bin/certbot /usr/bin/certbot
    
    print_warning "SSL certificate generation requires manual intervention"
    print_warning "Run the following commands after this script completes:"
    echo ""
    echo "sudo certbot certonly --standalone -d $DOMAIN -d www.$DOMAIN --email $EMAIL"
    echo "sudo cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem $PROJECT_DIR/ssl/$DOMAIN.crt"
    echo "sudo cp /etc/letsencrypt/live/$DOMAIN/privkey.pem $PROJECT_DIR/ssl/$DOMAIN.key"
    echo "sudo chown ubuntu:ubuntu $PROJECT_DIR/ssl/*"
    echo ""
    
    # Create temporary self-signed certificates for initial setup
    print_status "Creating temporary self-signed certificates..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout ssl/$DOMAIN.key -out ssl/$DOMAIN.crt \
        -subj "/C=US/ST=State/L=City/O=Organization/CN=$DOMAIN"
    
    print_status "Temporary SSL certificates created"
}

# Step 7: Create maintenance scripts
create_maintenance_scripts() {
    print_status "Creating maintenance scripts..."
    
    # Backup script
    cat > backup.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/dv-website/backups"

# Load environment variables
source /opt/dv-website/.env

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup databases
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} it_domain_db > $BACKUP_DIR/it_domain_backup_$DATE.sql
docker-compose -f /opt/dv-website/docker-compose.yml exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} nx_domain_db > $BACKUP_DIR/nx_domain_backup_$DATE.sql

# Compress backups
tar -czf $BACKUP_DIR/dv_backup_$DATE.tar.gz $BACKUP_DIR/*_$DATE.sql

# Remove individual SQL files
rm $BACKUP_DIR/*_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "dv_backup_*.tar.gz" -mtime +7 -delete

echo "Backup completed: dv_backup_$DATE.tar.gz"
EOF

    chmod +x backup.sh
    
    # Update script
    cat > update.sh << 'EOF'
#!/bin/bash
cd /opt/dv-website

echo "Updating Docker images..."
docker-compose pull

echo "Restarting services..."
docker-compose up -d

echo "Update completed"
EOF

    chmod +x update.sh
    
    # Status check script
    cat > status.sh << 'EOF'
#!/bin/bash
cd /opt/dv-website

echo "=== Container Status ==="
docker-compose ps

echo -e "\n=== Service Health ==="
docker-compose exec mysql mysqladmin ping -h localhost || echo "MySQL: DOWN"
curl -f -s http://localhost/it_website/ > /dev/null && echo "IT Domain: UP" || echo "IT Domain: DOWN"
curl -f -s http://localhost/nx_website/ > /dev/null && echo "NX Domain: UP" || echo "NX Domain: DOWN"

echo -e "\n=== Disk Usage ==="
df -h /opt/dv-website

echo -e "\n=== Recent Logs ==="
docker-compose logs --tail=5 mysql
EOF

    chmod +x status.sh
    
    print_status "Maintenance scripts created"
}

# Step 8: Setup firewall
setup_firewall() {
    print_status "Configuring firewall..."
    
    sudo ufw --force enable
    sudo ufw allow ssh
    sudo ufw allow 80
    sudo ufw allow 443
    sudo ufw deny 3306
    sudo ufw deny 3307
    
    print_status "Firewall configured"
}

# Step 9: Copy application files (this will be done by Claude on EC2)
copy_application_files() {
    print_status "Application files need to be copied to:"
    echo "  - IT Domain files → $PROJECT_DIR/it-domain/"
    echo "  - NX Domain files → $PROJECT_DIR/nx-domain/"
    echo "  - Database schemas → $PROJECT_DIR/database/"
    print_warning "Claude will help copy these files when running on EC2"
}

# Step 10: Deploy containers
deploy_containers() {
    print_status "Building and starting Docker containers..."
    
    cd $PROJECT_DIR
    
    # Build and start containers
    docker-compose up -d --build
    
    # Wait for services to be ready
    print_status "Waiting for services to start..."
    sleep 30
    
    # Check status
    docker-compose ps
    
    print_status "Container deployment completed"
}

# Main execution
main() {
    print_status "Starting automated deployment for $DOMAIN"
    
    # Check if Docker is already installed
    if ! command -v docker &> /dev/null; then
        install_docker
    else
        print_status "Docker already installed, skipping installation"
    fi
    
    setup_directories
    create_docker_configs
    create_dockerfiles
    create_nginx_config
    setup_ssl
    create_maintenance_scripts
    setup_firewall
    copy_application_files
    
    print_status "🎉 Automated setup completed!"
    print_warning "Next steps:"
    echo "1. Copy your application files to the appropriate directories"
    echo "2. Set up real SSL certificates (commands provided above)"
    echo "3. Run: cd $PROJECT_DIR && docker-compose up -d --build"
    echo "4. Visit https://$DOMAIN/it_website/ and https://$DOMAIN/nx_website/"
    echo ""
    echo "Useful commands:"
    echo "  - Check status: $PROJECT_DIR/status.sh"
    echo "  - Backup data: $PROJECT_DIR/backup.sh"
    echo "  - Update services: $PROJECT_DIR/update.sh"
}

# Run main function
main "$@"