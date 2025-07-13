# Quick Deployment Guide with Claude on EC2

## What Claude Can Help You With

With Claude installed on your EC2 instance, I can automate 90% of the deployment process for you! Here's what I can do:

### ✅ What I Can Automate
- Install Docker and Docker Compose
- Create all configuration files (docker-compose.yml, Dockerfiles, nginx config)
- Set up directory structure
- Configure firewall
- Copy your application files
- Build and deploy containers
- Set up maintenance scripts
- Generate SSL certificates

### ⚠️ What Requires Manual Steps
1. **DNS Setup**: Point `fengmzhu.men` to your EC2 IP
2. **Initial EC2 Access**: SSH into your instance
3. **SSL Email Configuration**: Provide your email for Let's Encrypt

## Step-by-Step Process

### 1. On Your Local Machine (Right Now)
```bash
# Transfer the deployment script to your EC2 instance
scp /workspace/dv_website/deploy.sh ubuntu@your-ec2-ip:~/
scp -r /workspace/dv_website/it-domain ubuntu@your-ec2-ip:~/
scp -r /workspace/dv_website/nx-domain ubuntu@your-ec2-ip:~/
scp -r /workspace/dv_website/database ubuntu@your-ec2-ip:~/
```

### 2. On Your EC2 Instance (With Claude)
Once you have Claude installed on EC2, I can run these commands for you:

```bash
# Make the script executable and run it
chmod +x ~/deploy.sh
~/deploy.sh
```

### 3. SSL Certificate Setup (Semi-Automated)
I can run these commands with your confirmation:
```bash
# Install Let's Encrypt certificate (requires your email)
sudo certbot certonly --standalone -d fengmzhu.men -d www.fengmzhu.men --email YOUR_EMAIL

# Copy certificates to project
sudo cp /etc/letsencrypt/live/fengmzhu.men/fullchain.pem /opt/dv-website/ssl/fengmzhu.men.crt
sudo cp /etc/letsencrypt/live/fengmzhu.men/privkey.pem /opt/dv-website/ssl/fengmzhu.men.key
sudo chown ubuntu:ubuntu /opt/dv-website/ssl/*

# Restart nginx with real certificates
cd /opt/dv-website && docker-compose restart nginx
```

### 4. Final Deployment
```bash
cd /opt/dv-website
docker-compose up -d --build
```

## What the Automated Script Does

The `deploy.sh` script I created will:

1. **Install Docker & Docker Compose** - Full automated installation
2. **Create Project Structure** - Sets up `/opt/dv-website/` with all subdirectories
3. **Generate All Config Files**:
   - `docker-compose.yml` - Multi-container orchestration
   - `Dockerfile` for both applications
   - `nginx/conf.d/default.conf` - Reverse proxy configuration
   - `.env` - Environment variables with secure passwords
4. **Setup SSL** - Installs certbot and creates temporary certificates
5. **Configure Firewall** - UFW rules for web traffic only
6. **Create Maintenance Scripts**:
   - `backup.sh` - Database backup automation
   - `status.sh` - Health check script
   - `update.sh` - Easy updates
7. **Copy Application Files** - Moves your code to the right locations
8. **Deploy Containers** - Builds and starts everything

## Time Estimation

- **Manual deployment**: 2-3 hours
- **With Claude automation**: 15-20 minutes

## What You Need to Provide

When I run the deployment on EC2, I'll need:

1. **Your email address** for SSL certificates
2. **Confirmation** for each major step
3. **DNS verification** that `fengmzhu.men` points to your EC2 IP

## Post-Deployment Commands

After deployment, I can help you with:

```bash
# Check everything is working
./status.sh

# View logs
docker-compose logs -f

# Backup your data
./backup.sh

# Update services
./update.sh
```

## Accessing Your Websites

After successful deployment:
- **IT Domain**: `https://fengmzhu.men/it_website/`
- **NX Domain**: `https://fengmzhu.men/nx_website/`
- **Main Page**: `https://fengmzhu.men/` (shows links to both)

## Advantages of This Approach

1. **Minimal Manual Work**: Just DNS setup and email confirmation
2. **Secure by Default**: Generates strong passwords, proper firewall rules
3. **Production Ready**: Includes SSL, backups, monitoring
4. **Easy Maintenance**: Scripts for common tasks
5. **Rollback Capability**: Docker makes it easy to revert changes

## Next Steps

1. **Set up DNS**: Point `fengmzhu.men` A record to your EC2 public IP
2. **Transfer files**: Copy the deployment script and application files to EC2
3. **Install Claude on EC2**: Follow Claude installation instructions
4. **Run deployment**: Let me execute the automated deployment script
5. **Test**: Verify both websites are accessible

The automated approach eliminates most of the complexity while ensuring you get a robust, production-ready deployment!