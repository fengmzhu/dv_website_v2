# DV Website Makefile
# Manage Docker-based DV Website deployment

# Variables
DOCKER_COMPOSE = docker compose
DOCKER_COMPOSE_FILE = docker/docker-compose.yml
DOCKER_COMPOSE_DEV = docker/docker-compose-working.yml
PROJECT_NAME = dv_website_v2
IT_DOMAIN_PORT = 8080
NX_DOMAIN_PORT = 8081

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[0;33m
RED = \033[0;31m
NC = \033[0m # No Color

# Default target
.DEFAULT_GOAL := help

# Phony targets
.PHONY: help build up down restart logs status clean test init db-init db-backup playwright lint

## Help
help:
	@echo "$(GREEN)DV Website Management Commands$(NC)"
	@echo ""
	@echo "$(YELLOW)Basic Operations:$(NC)"
	@echo "  make build        - Build Docker images"
	@echo "  make up           - Start all containers"
	@echo "  make down         - Stop all containers"
	@echo "  make restart      - Restart all containers"
	@echo "  make status       - Show container status"
	@echo "  make logs         - Show container logs"
	@echo ""
	@echo "$(YELLOW)Development:$(NC)"
	@echo "  make dev          - Start in development mode"
	@echo "  make shell-it     - Open shell in IT domain container"
	@echo "  make shell-nx     - Open shell in NX domain container"
	@echo ""
	@echo "$(YELLOW)Database:$(NC)"
	@echo "  make db-init      - Initialize databases with schemas"
	@echo "  make db-backup    - Backup all databases"
	@echo "  make db-restore   - Restore databases from backup"
	@echo ""
	@echo "$(YELLOW)Testing:$(NC)"
	@echo "  make test         - Run all tests"
	@echo "  make test-buttons - Test button functionality"
	@echo "  make playwright   - Run Playwright tests"
	@echo ""
	@echo "$(YELLOW)Maintenance:$(NC)"
	@echo "  make clean        - Clean up containers and volumes"
	@echo "  make clean-all    - Remove everything including images"
	@echo "  make lint         - Run code linting"
	@echo ""
	@echo "$(YELLOW)Quick Start:$(NC)"
	@echo "  make init         - Initialize and start everything"

## Initialize and start everything
init: build db-init up status
	@echo "$(GREEN)✅ DV Website initialized and running!$(NC)"
	@echo "$(YELLOW)IT Domain:$(NC) http://localhost:$(IT_DOMAIN_PORT)"
	@echo "$(YELLOW)NX Domain:$(NC) http://localhost:$(NX_DOMAIN_PORT)"

## Build Docker images
build:
	@echo "$(YELLOW)Building Docker images...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) build

## Start all containers
up:
	@echo "$(YELLOW)Starting containers...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) up -d
	@echo "$(GREEN)✅ Containers started$(NC)"

## Start in development mode
dev:
	@echo "$(YELLOW)Starting in development mode...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_DEV) up -d
	@echo "$(GREEN)✅ Development environment started$(NC)"

## Stop all containers
down:
	@echo "$(YELLOW)Stopping containers...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) down
	@echo "$(GREEN)✅ Containers stopped$(NC)"

## Restart all containers
restart: down up

## Show container logs
logs:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) logs -f

## Show logs for IT domain
logs-it:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) logs -f it-domain-web

## Show logs for NX domain
logs-nx:
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) logs -f nx-domain-web

## Show container status
status:
	@echo "$(YELLOW)Container Status:$(NC)"
	@docker ps --filter "name=$(PROJECT_NAME)" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

## Initialize databases
db-init:
	@echo "$(YELLOW)Initializing databases...$(NC)"
	@sleep 5  # Wait for MySQL to be ready
	@echo "Setting up IT Domain database..."
	@docker exec -i it-domain-mysql mysql -u root -proot_password < database/it-domain-schema.sql || true
	@docker exec -i it-domain-mysql mysql -u root -proot_password it_domain_db < database/it-domain-data.sql || true
	@echo "Setting up NX Domain database..."
	@docker exec -i nx-domain-mysql mysql -u root -proot_password < database/nx-domain-schema.sql || true
	@docker exec -i nx-domain-mysql mysql -u root -proot_password nx_domain_db < database/nx-domain-data.sql || true
	@echo "$(GREEN)✅ Databases initialized$(NC)"

## Backup databases
db-backup:
	@echo "$(YELLOW)Backing up databases...$(NC)"
	@mkdir -p backups
	@docker exec it-domain-mysql mysqldump -u root -proot_password it_domain_db > backups/it_domain_backup_$$(date +%Y%m%d_%H%M%S).sql
	@docker exec nx-domain-mysql mysqldump -u root -proot_password nx_domain_db > backups/nx_domain_backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Databases backed up to backups/$(NC)"

## Restore databases from latest backup
db-restore:
	@echo "$(YELLOW)Restoring databases from latest backup...$(NC)"
	@test -d backups || (echo "$(RED)No backups directory found$(NC)" && exit 1)
	@docker exec -i it-domain-mysql mysql -u root -proot_password it_domain_db < $$(ls -t backups/it_domain_backup_*.sql | head -1)
	@docker exec -i nx-domain-mysql mysql -u root -proot_password nx_domain_db < $$(ls -t backups/nx_domain_backup_*.sql | head -1)
	@echo "$(GREEN)✅ Databases restored$(NC)"

## Open shell in IT domain container
shell-it:
	docker exec -it it-domain-web /bin/bash

## Open shell in NX domain container
shell-nx:
	docker exec -it nx-domain-web /bin/bash

## Open MySQL shell for IT domain
mysql-it:
	docker exec -it it-domain-mysql mysql -u root -proot_password it_domain_db

## Open MySQL shell for NX domain
mysql-nx:
	docker exec -it nx-domain-mysql mysql -u root -proot_password nx_domain_db

## Run all tests
test: test-buttons playwright

## Test button functionality
test-buttons:
	@echo "$(YELLOW)Testing button functionality...$(NC)"
	@chmod +x scripts/analyze-buttons.sh scripts/test-button-interactions.sh
	@./scripts/analyze-buttons.sh
	@./scripts/test-button-interactions.sh

## Run Playwright tests
playwright:
	@echo "$(YELLOW)Running Playwright tests...$(NC)"
	@npm test

## Clean up containers and volumes
clean:
	@echo "$(YELLOW)Cleaning up containers and volumes...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) down -v
	@echo "$(GREEN)✅ Cleanup complete$(NC)"

## Remove everything including images
clean-all: clean
	@echo "$(YELLOW)Removing Docker images...$(NC)"
	@docker rmi $$(docker images -q $(PROJECT_NAME)* 2>/dev/null) 2>/dev/null || true
	@echo "$(GREEN)✅ Full cleanup complete$(NC)"

## Run code linting
lint:
	@echo "$(YELLOW)Running PHP linting...$(NC)"
	@find src -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

## Check ports
check-ports:
	@echo "$(YELLOW)Checking port availability...$(NC)"
	@lsof -i :$(IT_DOMAIN_PORT) >/dev/null 2>&1 && echo "$(RED)⚠️  Port $(IT_DOMAIN_PORT) is in use$(NC)" || echo "$(GREEN)✅ Port $(IT_DOMAIN_PORT) is available$(NC)"
	@lsof -i :$(NX_DOMAIN_PORT) >/dev/null 2>&1 && echo "$(RED)⚠️  Port $(NX_DOMAIN_PORT) is in use$(NC)" || echo "$(GREEN)✅ Port $(NX_DOMAIN_PORT) is available$(NC)"
	@lsof -i :3306 >/dev/null 2>&1 && echo "$(RED)⚠️  Port 3306 is in use$(NC)" || echo "$(GREEN)✅ Port 3306 is available$(NC)"
	@lsof -i :3307 >/dev/null 2>&1 && echo "$(RED)⚠️  Port 3307 is in use$(NC)" || echo "$(GREEN)✅ Port 3307 is available$(NC)"

## Tail logs for all containers
tail-logs:
	@echo "$(YELLOW)Tailing logs (Ctrl+C to stop)...$(NC)"
	$(DOCKER_COMPOSE) -f $(DOCKER_COMPOSE_FILE) logs -f --tail=50

## Health check
health:
	@echo "$(YELLOW)Checking health status...$(NC)"
	@echo ""
	@echo "IT Domain: "
	@curl -s http://localhost:$(IT_DOMAIN_PORT) >/dev/null && echo "$(GREEN)✅ Responding$(NC)" || echo "$(RED)❌ Not responding$(NC)"
	@echo ""
	@echo "NX Domain: "
	@curl -s http://localhost:$(NX_DOMAIN_PORT) >/dev/null && echo "$(GREEN)✅ Responding$(NC)" || echo "$(RED)❌ Not responding$(NC)"
	@echo ""
	@echo "IT Domain Database: "
	@docker exec it-domain-mysql mysqladmin -u root -proot_password ping >/dev/null 2>&1 && echo "$(GREEN)✅ Connected$(NC)" || echo "$(RED)❌ Not connected$(NC)"
	@echo ""
	@echo "NX Domain Database: "
	@docker exec nx-domain-mysql mysqladmin -u root -proot_password ping >/dev/null 2>&1 && echo "$(GREEN)✅ Connected$(NC)" || echo "$(RED)❌ Not connected$(NC)"

## Quick start (alias for init)
start: init

## Quick stop (alias for down)
stop: down